<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupplierRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Supplier;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Supplier $supplier, array $data): Supplier;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $term, int $limit = 10): array;
}
