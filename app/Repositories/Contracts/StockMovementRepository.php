<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockMovementRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, StockMovement>
     */
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator;

    /**
     * @return LengthAwarePaginator<int, StockMovement>
     */
    public function paginateForProduct(Product $product, int $perPage = 25): LengthAwarePaginator;
}
