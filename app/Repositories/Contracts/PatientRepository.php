<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PatientRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    public function create(array $data): Patient;

    public function update(Patient $patient, array $data): Patient;

    public function delete(Patient $patient): void;

    /**
     * Typeahead-friendly patient search.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $term, int $limit = 10): array;

    /**
     * @return Collection<int, Patient>
     */
    public function findDuplicates(?string $phone, ?string $name, ?string $email, ?int $ignoreId = null): Collection;
}
