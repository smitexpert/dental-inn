<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\NotificationDriver;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * High-level notification facade.
 *
 * - Renders templates via `str_replace` (no Blade).
 * - Sends through the configured driver (or a one-off override).
 * - Persists every attempt to `notification_logs` (immutable).
 * - Idempotent on (template_id, recipient, reference_id, day) so cron
 *   reminder jobs do not spam.
 */
class NotificationService
{
    public function __construct(private readonly NotificationDriver $driver) {}

    /**
     * Send a templated notification.
     *
     * @param  array<int, string>  $recipients
     * @param  array<int, string>  $recipients
     * @param  array<string, mixed>  $variables
     * @return array<int, array<string, mixed>>
     */
    public function send(
        string $templateKey,
        string $channelValue,
        array $recipients,
        array $variables,
        ?Model $reference = null,
        string $locale = 'en'
    ): array {
        $channel = NotificationChannel::from($channelValue);

        $template = NotificationTemplate::query()
            ->where('key', $templateKey)
            ->where('channel', $channel->value)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            throw new RuntimeException(
                "Active notification template '{$templateKey}' for channel '{$channel->value}' (locale {$locale}) was not found."
            );
        }

        $rendered = $this->renderTemplate($template, $variables);

        return $this->dispatch(
            template: $template,
            channel: $channel,
            recipients: $recipients,
            subject: $rendered['subject'],
            body: $rendered['body'],
            reference: $reference,
        );
    }

    /**
     * Send an ad-hoc notification without a template.
     *
     * @param  array<int, string>  $recipients
     * @return array<int, array<string, mixed>>
     */
    public function sendDirect(
        string $channelValue,
        array $recipients,
        ?string $subject,
        string $body,
        ?Model $reference = null,
    ): array {
        $channel = NotificationChannel::from($channelValue);

        return $this->dispatch(
            template: null,
            channel: $channel,
            recipients: $recipients,
            subject: $subject,
            body: $body,
            reference: $reference,
        );
    }

    /**
     * Render placeholders against a template. Returns
     * `['subject' => ?string, 'body' => string]`.
     *
     * @param  array<string, mixed>  $variables
     * @return array{subject: ?string, body: string}
     */
    public function renderTemplate(NotificationTemplate $template, array $variables): array
    {
        $body = $this->replace($template->body, $variables);
        $subject = $template->subject !== null
            ? $this->replace($template->subject, $variables)
            : null;

        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Light recipient validation per channel. Returns null if invalid.
     */
    public function resolveRecipient(string $channelValue, string $raw): ?string
    {
        $value = trim($raw);

        if ($value === '') {
            return null;
        }

        return match ($channelValue) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ?: null,
            'sms', 'whatsapp' => preg_match('/^\+?[1-9]\d{6,14}$/', $value) === 1 ? $value : null,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function replace(string $template, array $variables): string
    {
        $search = [];
        $replace = [];

        foreach ($variables as $key => $value) {
            $key = (string) $key;
            $search[] = '{{ '.$key.' }}';
            $replace[] = is_scalar($value) || $value === null ? (string) $value : '';
        }

        $rendered = str_replace($search, $replace, $template);

        // Catch placeholders we did not have variables for and surface
        // them so template authors spot mistakes quickly.
        if (preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $rendered, $matches) > 0) {
            throw new InvalidArgumentException(
                'Template references undefined variable(s): '.implode(', ', array_unique($matches[1]))
            );
        }

        return $rendered;
    }

    /**
     * Fan-out + per-recipient logging with idempotency.
     *
     * @param  array<int, string>  $recipients
     * @return array<int, array<string, mixed>>
     */
    private function dispatch(
        ?NotificationTemplate $template,
        NotificationChannel $channel,
        array $recipients,
        ?string $subject,
        string $body,
        ?Model $reference,
    ): array {
        $results = [];

        foreach ($recipients as $raw) {
            $recipient = $this->resolveRecipient($channel->value, $raw);

            if ($recipient === null) {
                $results[] = [
                    'recipient' => (string) $raw,
                    'logged' => false,
                    'sent' => false,
                    'error' => 'Invalid recipient for channel '.$channel->value,
                ];

                continue;
            }

            if ($template !== null && $this->alreadySentToday($template, $channel, $recipient, $reference)) {
                $results[] = [
                    'recipient' => $recipient,
                    'logged' => false,
                    'sent' => false,
                    'error' => null,
                    'skipped' => 'already_sent_today',
                ];

                continue;
            }

            $driverResult = $this->driver->send(
                channel: $channel->value,
                recipient: $recipient,
                subject: $subject,
                body: $body,
                meta: [
                    'template_key' => $template?->key,
                    'reference_type' => $reference !== null ? $reference->getMorphClass() : null,
                    'reference_id' => $reference?->getKey(),
                ],
            );

            $status = $driverResult['success']
                ? NotificationStatus::Sent
                : NotificationStatus::Failed;

            NotificationLog::query()->create([
                'template_id' => $template?->id,
                'channel' => $channel->value,
                'recipient' => $recipient,
                'subject' => $subject,
                'body_rendered' => $body,
                'status' => $status->value,
                'error_message' => $driverResult['error'],
                'reference_type' => $reference !== null ? $reference->getMorphClass() : null,
                'reference_id' => $reference?->getKey(),
                'sent_at' => $driverResult['success'] ? Carbon::now() : null,
                'created_at' => Carbon::now(),
            ]);

            $results[] = [
                'recipient' => $recipient,
                'logged' => true,
                'sent' => $driverResult['success'],
                'error' => $driverResult['error'],
                'provider_id' => $driverResult['provider_id'],
            ];

            if (! $driverResult['success']) {
                Log::warning('notification.failed', [
                    'template' => $template?->key,
                    'channel' => $channel->value,
                    'recipient' => $recipient,
                    'error' => $driverResult['error'],
                ]);
            }
        }

        return $results;
    }

    private function alreadySentToday(
        NotificationTemplate $template,
        NotificationChannel $channel,
        string $recipient,
        ?Model $reference,
    ): bool {
        $start = Carbon::now()->startOfDay();
        $end = Carbon::now()->endOfDay();

        $query = NotificationLog::query()
            ->where('template_id', $template->id)
            ->where('channel', $channel->value)
            ->where('recipient', $recipient)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end);

        if ($reference !== null) {
            $query->where('reference_type', $reference->getMorphClass())
                ->where('reference_id', $reference->getKey());
        } else {
            $query->whereNull('reference_type')
                ->whereNull('reference_id');
        }

        return $query->exists();
    }
}
