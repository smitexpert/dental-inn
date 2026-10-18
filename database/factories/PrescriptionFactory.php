<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DosageFrequency;
use App\Enums\MealRelation;
use App\Enums\PrescriptionStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prescription_number' => Prescription::generateUniqueNumber(),
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'appointment_id' => null,
            'treatment_record_id' => null,
            'issued_at' => null,
            'status' => PrescriptionStatus::Draft->value,
            'general_advice' => fake()->optional(0.5)->sentence(),
            'diagnosis_text' => fake()->optional(0.6)->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(fn (): array => ['patient_id' => $patient->id]);
    }

    public function forAppointment(Appointment $appointment): static
    {
        return $this->state(fn (): array => [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id ?? Patient::factory(),
        ]);
    }

    public function issued(): static
    {
        return $this->state(fn (): array => [
            'status' => PrescriptionStatus::Issued->value,
            'issued_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => PrescriptionStatus::Cancelled->value,
        ]);
    }
}