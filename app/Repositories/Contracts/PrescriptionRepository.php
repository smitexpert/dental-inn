<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PrescriptionRepository
{
    /**
     * @return LengthAwarePaginator<int, Prescription>
     */
    public function paginateForPatient(Patient $patient, int $perPage = 25): LengthAwarePaginator;

    public function find(int $id): Prescription;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Patient $patient, int $createdByUserId, array $data): Prescription;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Prescription $prescription, array $data): Prescription;

    public function issue(Prescription $prescription): Prescription;

    public function cancel(Prescription $prescription): Prescription;

    /**
     * @return Collection<int, Prescription>
     */
    public function listForAppointment(Appointment $appointment): Collection;
}
