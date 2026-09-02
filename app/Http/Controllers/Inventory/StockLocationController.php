<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStockLocationRequest;
use App\Http\Requests\Inventory\UpdateStockLocationRequest;
use App\Models\StockLocation;
use App\Repositories\Contracts\StockLocationRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StockLocationController extends Controller
{
    public function __construct(private readonly StockLocationRepository $locations) {}

    public function index(): Response
    {
        $page = $this->locations->paginate(
            request()->integer('per_page', 25),
            request()->only(['q', 'is_active']),
        );

        return Inertia::render('Inventory/Locations/Index', [
            'locations' => $page,
            'filters' => request()->only(['q', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Locations/Index', [
            'formMode' => 'create',
        ]);
    }

    public function store(StoreStockLocationRequest $request): RedirectResponse
    {
        $location = $this->locations->create($request->validated());

        return redirect()->route('inventory.locations.index')->with('success', 'Location created.');
    }

    public function edit(StockLocation $location): Response
    {
        return Inertia::render('Inventory/Locations/Index', [
            'formMode' => 'edit',
            'editing' => [
                'id' => $location->id,
                'name' => $location->name,
                'location_type' => $location->location_type->value,
                'is_active' => (bool) $location->is_active,
            ],
        ]);
    }

    public function update(UpdateStockLocationRequest $request, StockLocation $location): RedirectResponse
    {
        $this->locations->update($location, $request->validated());

        return redirect()->route('inventory.locations.index')->with('success', 'Location updated.');
    }
}
