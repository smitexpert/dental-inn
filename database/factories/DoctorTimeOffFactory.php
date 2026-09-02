<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\DoctorTimeOff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoctorTimeOff>
 */
class DoctorTimeOffFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+2 weeks');
        $end = (clone $start)->modify('+2 hours');

        return [
            'doctor_id' => Doctor::factory(),
            'start_at' => $start,
            'end_at' => $end,
            'reason' => fake()->randomElement(['Vacation', 'Sick leave', 'Conference']),
        ];
    }
}
