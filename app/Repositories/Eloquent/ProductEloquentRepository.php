<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductEloquentRepository implements ProductRepository
{
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = Product::query();

        if (! empty($filters['q'])) {
            $q->search($filters['q']);
        }

        if (! empty($filters['category'])) {
            $q->where('category', $filters['category']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $q->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['low_stock'])) {
            $q->lowStock();
        }

        return $q->orderBy('name')->paginate($perPage)->withQueryString();
    }

    public function create(array $data): Product
    {
        $product = new Product;
        $product->fill($data);
        $product->save();

        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        $product->fill($data);
        $product->save();

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function search(string $term, int $limit = 10): array
    {
        $q = Product::query()->active()->orderBy('name')->limit(max(1, min(50, $limit)));

        if ($term !== '') {
            $q->search($term);
        }

        return $q->get()
            ->map(fn (Product $p): array => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'unit' => $p->unit->value,
                'unit_label' => $p->unit->shortLabel(),
            ])
            ->all();
    }

    public function lowStockCount(): int
    {
        return Product::query()
            ->tracked()
            ->active()
            ->whereHas('stockLevels', function (Builder $q): void {
                $q->whereColumn('quantity', '<=', 'products.reorder_level');
            })
            ->count();
    }

    public function lowStockQuery(): Builder
    {
        return Product::query()
            ->tracked()
            ->active()
            ->whereHas('stockLevels', function (Builder $q): void {
                $q->whereColumn('quantity', '<=', 'products.reorder_level');
            })
            ->orderBy('name');
    }
}
