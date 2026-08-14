<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\TreatmentStatus;
use App\Models\Patient;
use App\Models\TreatmentRecord;
use App\Repositories\Contracts\TreatmentRecordRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class TreatmentRecordEloquentRepository implements TreatmentRecordRepository
{
    /**
     * @return LengthAwarePaginator<int, TreatmentRecord>
     */
    public function paginateForPatient(Patient $patient, int $perPage = 25): LengthAwarePaginator
    {
        return TreatmentRecord::query()
            ->with('doctor.user')
            ->forPatient($patient)
            ->orderByDesc('visited_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): TreatmentRecord
    {
        return TreatmentRecord::query()
            ->with(['patient', 'doctor.user', 'appointment'])
            ->findOrFail($id);
    }

    public function create(array $data): TreatmentRecord
    {
        $record = new TreatmentRecord;
        $record->fill($data);
        $record->status = $data['status'] ?? TreatmentStatus::InProgress->value;
        $record->save();

        return $record;
    }

    public function update(TreatmentRecord $record, array $data): TreatmentRecord
    {
        $record->fill($data);
        $record->save();

        return $record;
    }

    public function markCompleted(TreatmentRecord $record): TreatmentRecord
    {
        $record->status = TreatmentStatus::Completed;
        $record->save();

        activity()
            ->performedOn($record)
            ->log('Treatment record completed');

        return $record;
    }

    public function cancel(TreatmentRecord $record): TreatmentRecord
    {
        $record->status = TreatmentStatus::Cancelled;
        $record->save();

        activity()
            ->performedOn($record)
            ->log('Treatment record cancelled');

        return $record;
    }
}
