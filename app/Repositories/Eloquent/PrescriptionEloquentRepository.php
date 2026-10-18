<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\PrescriptionStatus;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Repositories\Contracts\PrescriptionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PrescriptionEloquentRepository implements PrescriptionRepository
{
    /**
     * @return LengthAwarePaginator<int, Prescription>
     */
    public function paginateForPatient(Patient $patient, int $perPage = 25): LengthAwarePaginator
    {
        return Prescription::query()
            ->with(['doctor.user', 'appointment', 'items'])
            ->forPatient($patient)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): Prescription
    {
        return Prescription::query()
            ->with(['patient', 'doctor.user', 'appointment', 'items.medicine', 'creator'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Patient $patient, int $createdByUserId, array $data): Prescription
    {
        $prescription = new Prescription;
        $prescription->fill([
            'prescription_number' => Prescription::generateUniqueNumber(),
            'patient_id' => $patient->id,
            'doctor_id' => $data['doctor_id'] ?? null,
            'appointment_id' => $data['appointment_id'] ?? null,
            'treatment_record_id' => $data['treatment_record_id'] ?? null,
            'status' => PrescriptionStatus::Draft->value,
            'general_advice' => $data['general_advice'] ?? null,
            'diagnosis_text' => $data['diagnosis_text'] ?? null,
            'created_by' => $createdByUserId,
        ]);
        $prescription->save();

        $this->syncItems($prescription, $data['items'] ?? []);

        return $prescription->refresh()->load(['items.medicine', 'doctor.user', 'appointment']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Prescription $prescription, array $data): Prescription
    {
        $prescription->fill([
            'doctor_id' => $data['doctor_id'] ?? $prescription->doctor_id,
            'general_advice' => $data['general_advice'] ?? $prescription->general_advice,
            'diagnosis_text' => $data['diagnosis_text'] ?? $prescription->diagnosis_text,
        ]);
        $prescription->save();

        if (array_key_exists('items', $data)) {
            $prescription->items()->delete();
            $this->syncItems($prescription, $data['items']);
        }

        return $prescription->refresh()->load(['items.medicine', 'doctor.user', 'appointment']);
    }

    public function issue(Prescription $prescription): Prescription
    {
        $prescription->status = PrescriptionStatus::Issued;
        $prescription->issued_at = now();
        $prescription->save();

        activity()
            ->performedOn($prescription)
            ->log('Prescription issued');

        return $prescription;
    }

    public function cancel(Prescription $prescription): Prescription
    {
        $prescription->status = PrescriptionStatus::Cancelled;
        $prescription->save();

        activity()
            ->performedOn($prescription)
            ->log('Prescription cancelled');

        return $prescription;
    }

    /**
     * @return Collection<int, Prescription>
     */
    public function listForAppointment(Appointment $appointment): Collection
    {
        return Prescription::query()
            ->with(['items', 'doctor.user'])
            ->forAppointment($appointment->id)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Prescription $prescription, array $items): void
    {
        foreach (array_values($items) as $index => $raw) {
            $item = new PrescriptionItem;
            $item->fill([
                'prescription_id' => $prescription->id,
                'medicine_id' => $raw['medicine_id'] ?? null,
                'medicine_name' => (string) ($raw['medicine_name'] ?? ''),
                'strength' => $raw['strength'] ?? null,
                'dosage' => (string) ($raw['dosage'] ?? ''),
                'frequency' => (string) ($raw['frequency'] ?? ''),
                'frequency_label_bn' => $raw['frequency_label_bn'] ?? null,
                'duration' => $raw['duration'] ?? null,
                'meal_relation' => (string) ($raw['meal_relation'] ?? ''),
                'meal_relation_label_bn' => $raw['meal_relation_label_bn'] ?? null,
                'instructions' => $raw['instructions'] ?? null,
                'quantity' => (int) ($raw['quantity'] ?? 1),
                'sort_order' => (int) ($raw['sort_order'] ?? $index),
            ]);
            $item->save();
        }
    }
}
