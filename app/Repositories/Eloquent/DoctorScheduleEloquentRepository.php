<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Repositories\Contracts\DoctorScheduleRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DoctorScheduleEloquentRepository implements DoctorScheduleRepository
{
    public function forDoctor(Doctor $doctor): Collection
    {
        return DoctorSchedule::query()
            ->where('doctor_id', $doctor->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    public function replaceForDoctor(Doctor $doctor, array $rows): Collection
    {
        return DB::transaction(function () use ($doctor, $rows): Collection {
            DoctorSchedule::query()->where('doctor_id', $doctor->id)->delete();

            $insert = [];
            $now = now();
            foreach ($rows as $row) {
                if (! isset($row['day_of_week'], $row['start_time'], $row['end_time'])) {
                    continue;
                }
                $insert[] = [
                    'doctor_id' => $doctor->id,
                    'day_of_week' => (int) $row['day_of_week'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'is_active' => (bool) ($row['is_active'] ?? true),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($insert !== []) {
                DoctorSchedule::query()->insert($insert);
            }

            return $this->forDoctor($doctor);
        });
    }
}
