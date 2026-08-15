<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Doctor;
use Illuminate\Pagination\LengthAwarePaginator;

interface DoctorRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function create(array $data): Doctor;

    public function update(Doctor $doctor, array $data): Doctor;

    public function toggleAvailability(Doctor $doctor): Doctor;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActive(): array;
}
