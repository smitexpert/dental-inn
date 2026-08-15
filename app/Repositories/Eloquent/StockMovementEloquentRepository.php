<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockMovementEloquentRepository implements StockMovementRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, StockMovement>
     */
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = StockMovement::query()->with(['product', 'location', 'performer']);

        if (! empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }

        if (! empty($filters['product_id'])) {
            $q->where('product_id', (int) $filters['product_id']);
        }

        if (! empty($filters['location_id'])) {
            $q->where('location_id', (int) $filters['location_id']);
        }

        return $q->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, StockMovement>
     */
    public function paginateForProduct(Product $product, int $perPage = 25): LengthAwarePaginator
    {
        return StockMovement::query()
            ->with(['location', 'performer'])
            ->where('product_id', $product->id)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
