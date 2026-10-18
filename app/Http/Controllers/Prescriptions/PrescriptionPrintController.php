<?php

declare(strict_types=1);

namespace App\Http\Controllers\Prescriptions;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Prescription;
use App\Repositories\Contracts\PrescriptionRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PrescriptionPrintController extends Controller
{
    public function __construct(
        private readonly PrescriptionRepository $prescriptions,
    ) {}

    public function show(Patient $patient, Prescription $prescription): Response|RedirectResponse
    {
        abort_unless((int) $prescription->patient_id === (int) $patient->id, 404);
        $prescription->load(['patient', 'doctor.user', 'appointment', 'items']);

        return Inertia::render('Prescriptions/Print', [
            'patient' => [
                'id' => $patient->id,
                'patient_code' => $patient->patient_code,
                'name' => $patient->name,
                'age' => $patient->age,
                'phone' => $patient->phone,
                'gender' => $patient->gender?->value,
            ],
            'prescription' => [
                'prescription_number' => $prescription->prescription_number,
                'doctor_name' => $prescription->doctor?->user?->name,
                'doctor_color' => $prescription->doctor?->color,
                'issued_at' => $prescription->issued_at?->toDateTimeString(),
                'general_advice' => $prescription->general_advice,
                'diagnosis_text' => $prescription->diagnosis_text,
                'items' => $prescription->items->map(fn ($i): array => [
                    'medicine_name' => $i->medicine_name,
                    'strength' => $i->strength,
                    'dosage' => $i->dosage,
                    'frequency' => $i->frequency,
                    'frequency_label_bn' => $i->frequency_label_bn,
                    'duration' => $i->duration,
                    'meal_relation' => $i->meal_relation,
                    'meal_relation_label_bn' => $i->meal_relation_label_bn,
                    'instructions' => $i->instructions,
                    'quantity' => $i->quantity,
                    'summary_bn' => $i->summary_bn,
                ])->values(),
            ],
        ]);
    }
}
