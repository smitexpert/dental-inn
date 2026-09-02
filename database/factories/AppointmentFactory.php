<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+2 weeks');

        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'scheduled_at' => $start,
            'duration_minutes' => 30,
            'status' => AppointmentStatus::Scheduled,
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }

    public function scheduledAt(\DateTimeInterface|string $when): static
    {
        return $this->state(fn (): array => ['scheduled_at' => $when]);
    }

    public function withStatus(AppointmentStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }
}
