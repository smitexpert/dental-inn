<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Doctor;
use App\Models\DoctorTimeOff;
use App\Repositories\Contracts\DoctorTimeOffRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class DoctorTimeOffEloquentRepository implements DoctorTimeOffRepository
{
    public function paginate(Doctor $doctor, int $perPage = 15): LengthAwarePaginator
    {
        return DoctorTimeOff::query()
            ->where('doctor_id', $doctor->id)
            ->orderBy('start_at', 'desc')
            ->paginate($perPage);
    }

    public function create(Doctor $doctor, array $data): DoctorTimeOff
    {
        return DoctorTimeOff::create([
            'doctor_id' => $doctor->id,
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'reason' => $data['reason'] ?? null,
        ]);
    }

    public function delete(DoctorTimeOff $timeOff): void
    {
        $timeOff->delete();
    }

    /**
     * @return array<int, DoctorTimeOff>
     */
    public function overlapping(Doctor $doctor, Carbon $start, Carbon $end): array
    {
        return DoctorTimeOff::query()
            ->where('doctor_id', $doctor->id)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->get()
            ->all();
    }
}
