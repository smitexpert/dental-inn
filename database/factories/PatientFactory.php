<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        $genders = array_map(fn (Gender $c) => $c->value, Gender::cases());

        return [
            'patient_code' => '',
            'name' => fake()->name(),
            'phone' => '+1-555-'.fake()->unique()->numerify('####'),
            'email' => fake()->boolean(60) ? fake()->safeEmail() : null,
            'date_of_birth' => fake()->boolean(70) ? fake()->dateTimeBetween('-80 years', '-5 years')->format('Y-m-d') : null,
            'gender' => fake()->boolean(70) ? fake()->randomElement($genders) : null,
            'address_line_1' => fake()->boolean(50) ? fake()->streetAddress() : null,
            'address_line_2' => null,
            'city' => fake()->boolean(50) ? fake()->city() : null,
            'state' => fake()->boolean(50) ? fake()->state() : null,
            'postal_code' => fake()->boolean(50) ? fake()->postcode() : null,
            'country' => 'Bangladesh',
            'emergency_contact_name' => fake()->boolean(30) ? fake()->name() : null,
            'emergency_contact_phone' => fake()->boolean(30) ? '+1-555-'.fake()->numerify('####') : null,
            'medical_history' => fake()->boolean(30) ? fake()->sentence() : null,
            'dental_history' => null,
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    public function withPhone(string $phone): static
    {
        return $this->state(fn (): array => ['phone' => $phone]);
    }

    public function withMedicalHistory(): static
    {
        return $this->state(fn (): array => [
            'medical_history' => 'Penicillin allergy. Hypertension controlled with medication.',
            'dental_history' => 'Two prior fillings (2018, 2022). No surgeries.',
        ]);
    }
}
