<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'template_id' => null,
            'channel' => NotificationChannel::Sms,
            'recipient' => '+8801'.fake()->numerify('#########'),
            'subject' => null,
            'body_rendered' => 'Hello, this is a test.',
            'status' => NotificationStatus::Sent,
            'error_message' => null,
            'reference_type' => null,
            'reference_id' => null,
            'sent_at' => now(),
            'created_at' => now(),
        ];
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => NotificationStatus::Failed,
            'error_message' => 'Recipient is offline.',
        ]);
    }
}
