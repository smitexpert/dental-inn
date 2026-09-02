<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contract every notification delivery driver must implement.
 *
 * Drivers are responsible for taking a rendered notification (channel,
 * recipient, optional subject, body) and pushing it through whatever
 * side effect the channel requires (HTTP webhook, log line, mail
 * transport). They MUST NOT throw on transient errors — they should
 * catch, log internally, and return a structured result so the
 * caller can record an immutable `notification_logs` row.
 */
interface NotificationDriver
{
    /**
     * Send a single notification.
     *
     * @param  string  $channel  One of sms|whatsapp|email
     * @param  string  $recipient  E.164 phone or email address
     * @param  ?string  $subject  Email subject (null for sms/whatsapp)
     * @param  string  $body  Rendered body, UTF-8
     * @param  array<string, mixed>  $meta  Free-form driver hints
     * @return array{success: bool, error: ?string, provider_id: ?string}
     */
    public function send(
        string $channel,
        string $recipient,
        ?string $subject,
        string $body,
        array $meta = []
    ): array;
}
