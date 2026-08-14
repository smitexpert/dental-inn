<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Patient;
use App\Models\TreatmentRecord;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TreatmentRecordRepository
{
    /**
     * @return LengthAwarePaginator<int, TreatmentRecord>
     */
    public function paginateForPatient(Patient $patient, int $perPage = 25): LengthAwarePaginator;

    public function find(int $id): TreatmentRecord;

    public function create(array $data): TreatmentRecord;

    public function update(TreatmentRecord $record, array $data): TreatmentRecord;

    public function markCompleted(TreatmentRecord $record): TreatmentRecord;

    public function cancel(TreatmentRecord $record): TreatmentRecord;
}
