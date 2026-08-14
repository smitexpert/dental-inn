<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Patient;
use App\Repositories\Contracts\PatientRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PatientEloquentRepository implements PatientRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $q = Patient::query();

        if (! empty($filters['q'])) {
            $q->search($filters['q']);
        }
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $q->where('is_active', (bool) $filters['is_active']);
        }

        return $q->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Patient
    {
        $patient = new Patient();
        $patient->fill(array_diff_key($data, array_flip(['patient_code'])));
        $patient->save();

        return $patient;
    }

    public function update(Patient $patient, array $data): Patient
    {
        $patient->fill(array_diff_key($data, array_flip(['patient_code'])));
        $patient->save();

        return $patient;
    }

    public function delete(Patient $patient): void
    {
        $patient->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $term, int $limit = 10): array
    {
        $q = Patient::query()->orderBy('name')->limit(max(1, min(50, $limit)));

        if ($term !== '') {
            $q->search($term);
        }

        return $q->get()
            ->map(fn (Patient $p): array => [
                'id' => $p->id,
                'patient_code' => $p->patient_code,
                'name' => $p->name,
                'phone' => $p->phone,
            ])
            ->all();
    }

    /**
     * @return Collection<int, Patient>
     */
    public function findDuplicates(?string $phone, ?string $name, ?string $email, ?int $ignoreId = null): Collection
    {
        if (! $phone && (! $name || ! $email)) {
            return new Collection();
        }

        return Patient::query()
            ->duplicatesOf($phone, $name, $email, $ignoreId)
            ->orderBy('name')
            ->limit(10)
            ->get();
    }
}
