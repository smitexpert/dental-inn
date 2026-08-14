<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Doctor;
use App\Models\DoctorTimeOff;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

interface DoctorTimeOffRepository
{
    public function paginate(Doctor $doctor, int $perPage = 15): LengthAwarePaginator;

    public function create(Doctor $doctor, array $data): DoctorTimeOff;

    public function delete(DoctorTimeOff $timeOff): void;

    /**
     * @return array<int, DoctorTimeOff>
     */
    public function overlapping(Doctor $doctor, Carbon $start, Carbon $end): array;
}
