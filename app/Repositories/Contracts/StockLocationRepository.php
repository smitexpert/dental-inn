<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\StockLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockLocationRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): StockLocation;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(StockLocation $location, array $data): StockLocation;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activeList(): array;
}
