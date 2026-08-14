<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface AppointmentRepository
{
    /**
     * @return Collection<int, Appointment>
     */
    public function between(Carbon $start, Carbon $end, ?Doctor $doctor = null): Collection;

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function create(array $data): Appointment;

    public function update(Appointment $appointment, array $data): Appointment;

    public function delete(Appointment $appointment): void;

    public function updateStatus(Appointment $appointment, AppointmentStatus $new, ?string $reason = null): Appointment;

    public function overlappingCount(int $doctorId, Carbon $start, Carbon $end, ?int $ignoreId = null): int;
}
