<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Collection;

interface DoctorScheduleRepository
{
    public function forDoctor(Doctor $doctor): Collection;

    public function replaceForDoctor(Doctor $doctor, array $rows): Collection;
}
