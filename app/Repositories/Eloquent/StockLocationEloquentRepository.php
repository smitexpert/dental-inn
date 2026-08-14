<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\StockLocation;
use App\Repositories\Contracts\StockLocationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockLocationEloquentRepository implements StockLocationRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = StockLocation::query()->withCount('stockLevels');

        if (! empty($filters['q'])) {
            $q->where('name', 'like', '%'.$filters['q'].'%');
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $q->where('is_active', (bool) $filters['is_active']);
        }

        return $q->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function create(array $data): StockLocation
    {
        $location = new StockLocation;
        $location->fill($data);
        $location->save();

        return $location;
    }

    public function update(StockLocation $location, array $data): StockLocation
    {
        $location->fill($data);
        $location->save();

        return $location;
    }

    public function activeList(): array
    {
        return StockLocation::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'location_type'])
            ->map(fn (StockLocation $l): array => [
                'id' => $l->id,
                'name' => $l->name,
                'location_type' => $l->location_type->value,
                'location_type_label' => $l->location_type->label(),
            ])
            ->all();
    }
}
