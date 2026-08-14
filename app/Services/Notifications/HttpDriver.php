<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\NotificationDriver;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * POSTs the notification payload as JSON to a configured webhook URL.
 * Operators can point this at any gateway provider that accepts a
 * generic JSON contract: Twilio, MessageBird, a custom internal relay, etc.
 */
class HttpDriver implements NotificationDriver
{
    /**
     * @param  array{url?: ?string, timeout?: int}  $config
     */
    public function __construct(private readonly array $config = [])
    {}

    public function send(
        string $channel,
        string $recipient,
        ?string $subject,
        string $body,
        array $meta = []
    ): array {
        $url = $this->config['url'] ?? null;

        if ($url === null || $url === '') {
            return [
                'success' => false,
                'error' => 'HTTP driver is missing url configuration.',
                'provider_id' => null,
            ];
        }

        $timeout = (int) ($this->config['timeout'] ?? 5);

        $payload = [
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'meta' => $meta,
        ];

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'error' => 'HTTP '.$response->status().': '.$response->body(),
                    'provider_id' => null,
                ];
            }

            $data = $response->json();
            $providerId = is_array($data) && isset($data['id'])
                ? (string) $data['id']
                : null;

            return [
                'success' => true,
                'error' => null,
                'provider_id' => $providerId,
            ];
        } catch (RuntimeException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
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
