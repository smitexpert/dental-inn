<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Contracts\NotificationDriver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Default driver: writes a structured log line instead of contacting
 * any external provider. Used in dev, tests, and CI.
 */
class LogDriver implements NotificationDriver
{
    public function send(
        string $channel,
        string $recipient,
        ?string $subject,
        string $body,
        array $meta = []
    ): array {
        try {
            $providerId = (string) Str::uuid();

            Log::info('notification.sent', [
                'channel' => $channel,
                'recipient' => $recipient,
                'subject' => $subject,
                'body' => $body,
                'meta' => $meta,
                'provider_id' => $providerId,
            ]);

            return [
                'success' => true,
                'error' => null,
                'provider_id' => $providerId,
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
