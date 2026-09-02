<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreProductRequest;
use App\Http\Requests\Inventory\UpdateProductRequest;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepository;
use App\Repositories\Contracts\StockLocationRepository;
use App\Repositories\Contracts\StockMovementRepository;
use App\Repositories\Contracts\SupplierRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly StockMovementRepository $movements,
        private readonly StockLocationRepository $locations,
        private readonly SupplierRepository $suppliers,
    ) {}

    public function index(): Response
    {
        $page = $this->products->paginate(
            request()->integer('per_page', 25),
            request()->only(['q', 'category', 'is_active', 'low_stock']),
        );

        return Inertia::render('Inventory/Products/Index', [
            'products' => $page,
            'filters' => request()->only(['q', 'category', 'is_active', 'low_stock']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Products/Create', [
            'suppliers' => $this->suppliers->search('', 50),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated());

        return redirect()->route('inventory.products.show', $product)->with('success', 'Product created.');
    }

    public function show(Product $product): Response
    {
        $product->load(['stockLevels.location', 'suppliers']);

        $recentMovements = $this->movements->paginateForProduct($product, 20);

        return Inertia::render('Inventory/Products/Show', [
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'category' => $product->category->value,
                'category_label' => $product->category->label(),
                'unit' => $product->unit->value,
                'unit_label' => $product->unit->label(),
                'unit_short' => $product->unit->shortLabel(),
                'cost_price' => $product->cost_price,
                'selling_price' => $product->selling_price,
                'reorder_level' => $product->reorder_level,
                'reorder_quantity' => $product->reorder_quantity,
                'description' => $product->description,
                'is_active' => (bool) $product->is_active,
                'track_stock' => (bool) $product->track_stock,
                'total_quantity' => $product->total_quantity,
                'lowest_quantity' => $product->lowest_quantity,
                'is_low' => $product->isLow(),
                'stock_levels' => $product->stockLevels->map(fn ($l) => [
                    'id' => $l->id,
                    'location_id' => $l->location_id,
                    'location_name' => $l->location->name,
                    'quantity' => (int) $l->quantity,
                    'last_counted_at' => $l->last_counted_at?->toIso8601String(),
                ])->all(),
                'suppliers' => $product->suppliers->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'supplier_sku' => $s->pivot->supplier_sku,
                    'unit_cost' => $s->pivot->unit_cost,
                    'lead_time_days' => $s->pivot->lead_time_days,
                    'is_preferred' => (bool) $s->pivot->is_preferred,
                ])->all(),
            ],
            'recent_movements' => $recentMovements->getCollection()->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type->value,
                'type_label' => $m->type->label(),
                'quantity' => (int) $m->quantity,
                'location_name' => $m->location?->name,
                'performer_name' => $m->performer?->name,
                'reason' => $m->reason,
                'created_at' => $m->created_at?->toIso8601String(),
            ])->all(),
            'locations' => $this->locations->activeList(),
            'suppliers' => $this->suppliers->search('', 50),
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Inventory/Products/Edit', [
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'category' => $product->category->value,
                'unit' => $product->unit->value,
                'cost_price' => $product->cost_price,
                'selling_price' => $product->selling_price,
                'reorder_level' => $product->reorder_level,
                'reorder_quantity' => $product->reorder_quantity,
                'description' => $product->description,
                'is_active' => (bool) $product->is_active,
                'track_stock' => (bool) $product->track_stock,
            ],
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->validated());

        return redirect()->route('inventory.products.show', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->products->delete($product);

        return redirect()->route('inventory.products.index')->with('success', 'Product archived.');
    }

    public function movements(Product $product): Response
    {
        $page = $this->movements->paginateForProduct($product, 50);

        return Inertia::render('Inventory/Products/Movements', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
            ],
            'movements' => $page->getCollection()->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type->value,
                'type_label' => $m->type->label(),
                'quantity' => (int) $m->quantity,
                'location_name' => $m->location?->name,
                'performer_name' => $m->performer?->name,
                'reason' => $m->reason,
                'unit_cost' => $m->unit_cost,
                'created_at' => $m->created_at?->toIso8601String(),
            ])->all(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = (string) $request->string('q', '');
        $limit = (int) $request->integer('limit', 10);

        return response()->json([
            'data' => $this->products->search($term, $limit),
        ]);
    }
}
