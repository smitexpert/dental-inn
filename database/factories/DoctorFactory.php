<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'user_id' => $user->id,
            'specialization' => fake()->randomElement(['Orthodontics', 'Endodontics', 'Periodontics', 'General Dentistry']),
            'license_number' => 'BMDC-'.fake()->unique()->numerify('#####'),
            'bio' => fake()->sentence(),
            'years_of_experience' => fake()->numberBetween(1, 30),
            'consultation_fee' => fake()->randomElement([500, 800, 1000, 1500]),
            'color' => fake()->randomElement(['#0EA5E9', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6']),
            'is_available' => true,
        ];
    }

    public function asDoctor(): static
    {
        return $this->afterCreating(function (Doctor $doctor): void {
            $role = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
            $doctor->user->assignRole($role);
        });
    }

    public function unavailable(): static
    {
        return $this->state(fn (): array => ['is_available' => false]);
    }
}
