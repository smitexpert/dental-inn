<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\NotificationDriver;
use Illuminate\Support\Facades\Mail;

/**
 * Email-only driver. Wraps Laravel's Mail facade so we can render the
 * rendered subject + body through the local mail transport without
 * forcing a queue. Non-email channels fall through to a failure result.
 */
class MailDriver implements NotificationDriver
{
    public function send(
        string $channel,
        string $recipient,
        ?string $subject,
        string $body,
        array $meta = []
    ): array {
        if ($channel !== 'email') {
            return [
                'success' => false,
                'error' => 'Mail driver only supports the email channel.',
                'provider_id' => null,
            ];
        }

        $subject = $subject ?? '(no subject)';

        try {
            Mail::raw($body, function ($message) use ($recipient, $subject): void {
                $message->to($recipient)->subject($subject);
            });

            return [
                'success' => true,
                'error' => null,
                'provider_id' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider_id' => null,
            ];
        }
    }
}
