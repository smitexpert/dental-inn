<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TreatmentStatus;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\TreatmentRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreatmentRecord>
 */
class TreatmentRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'appointment_id' => null,
            'visited_at' => now()->subDays(fake()->numberBetween(0, 30)),
            'chief_complaint' => fake()->randomElement([
                'Toothache in upper right',
                'Routine cleaning',
                'Cavity on lower left molar',
                'Sensitivity to cold',
                'Broken filling',
            ]),
            'diagnosis' => fake()->optional(0.7)->sentence(),
            'treatment_notes' => fake()->optional(0.7)->paragraph(),
            'tooth_chart' => null,
            'follow_up_date' => fake()->optional(0.3)->dateTimeBetween('+1 week', '+2 months')?->format('Y-m-d'),
            'status' => TreatmentStatus::InProgress->value,
        ];
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn (): array => ['patient_id' => $patient->id]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => TreatmentStatus::Completed->value,
            'diagnosis' => 'Procedure completed successfully.',
            'treatment_notes' => 'Patient tolerated procedure well. Post-op instructions given.',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => TreatmentStatus::Cancelled->value,
        ]);
    }

    public function withToothChart(): static
    {
        return $this->state(fn (): array => [
            'tooth_chart' => [
                'teeth' => [
                    [
                        'fdi' => fake()->randomElement([11, 16, 21, 26, 36, 46]),
                        'procedures' => [
                            [
                                'code' => 'D0140',
                                'name' => 'Limited oral evaluation',
                                'surface' => 'occlusal',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
