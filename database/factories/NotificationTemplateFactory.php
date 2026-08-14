<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationTemplate>
 */
class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    public function definition(): array
    {
        return [
            'key' => 'sample-'.fake()->unique()->slug(2),
            'channel' => NotificationChannel::Sms,
            'subject' => null,
            'body' => 'Hello {{ name }}, this is a test message.',
            'is_active' => true,
            'locale' => 'en',
        ];
    }

    public function email(): static
    {
        return $this->state(fn () => [
            'channel' => NotificationChannel::Email,
            'subject' => 'Hello {{ name }}',
        ]);
    }

    public function whatsapp(): static
    {
        return $this->state(fn () => [
            'channel' => NotificationChannel::Whatsapp,
        ]);
    }
}
