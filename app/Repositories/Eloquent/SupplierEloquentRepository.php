<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierEloquentRepository implements SupplierRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = Supplier::query();

        if (! empty($filters['q'])) {
            $q->search($filters['q']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $q->where('is_active', (bool) $filters['is_active']);
        }

        return $q->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Supplier
    {
        $supplier = new Supplier;
        $supplier->fill($data);
        $supplier->save();

        return $supplier;
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->fill($data);
        $supplier->save();

        return $supplier;
    }

    public function search(string $term, int $limit = 10): array
    {
        $q = Supplier::query()->active()->orderBy('name')->limit(max(1, min(50, $limit)));

        if ($term !== '') {
            $q->search($term);
        }

        return $q->get()
            ->map(fn (Supplier $s): array => [
                'id' => $s->id,
                'name' => $s->name,
                'phone' => $s->phone,
            ])
            ->all();
    }
}
