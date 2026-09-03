<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreSupplierRequest;
use App\Http\Requests\Inventory\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function __construct(private readonly SupplierRepository $suppliers) {}

    public function index(): Response
    {
        $page = $this->suppliers->paginate(
            request()->integer('per_page', 25),
            request()->only(['q', 'is_active']),
        );

        $kpis = [
            ['id' => 'total', 'label' => 'Total suppliers',
                'value' => (string) Supplier::count(), 'format' => 'number'],
            ['id' => 'active', 'label' => 'Active',
                'value' => (string) Supplier::where('is_active', true)->count(), 'format' => 'number'],
            ['id' => 'top', 'label' => 'Top supplier',
                'value' => Supplier::orderByDesc('id')->value('name') ?? '—'],
        ];

        return Inertia::render('Inventory/Suppliers/Index', [
            'suppliers' => $page,
            'filters' => request()->only(['q', 'is_active']),
            'kpis' => $kpis,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Suppliers/Create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = $this->suppliers->create($request->validated());

        return redirect()->route('inventory.suppliers.show', $supplier)->with('success', 'Supplier created.');
    }

    public function show(Supplier $supplier): Response
    {
        $supplier->load(['products' => fn ($q) => $q->orderBy('name')->limit(100)]);

        return Inertia::render('Inventory/Suppliers/Show', [
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contact_person' => $supplier->contact_person,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'notes' => $supplier->notes,
                'is_active' => (bool) $supplier->is_active,
                'created_at' => $supplier->created_at?->toIso8601String(),
            ],
            'products' => $supplier->products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'unit_short' => $p->unit->shortLabel(),
                'supplier_sku' => $p->pivot->supplier_sku,
                'unit_cost' => $p->pivot->unit_cost,
                'lead_time_days' => $p->pivot->lead_time_days,
                'is_preferred' => (bool) $p->pivot->is_preferred,
            ])->all(),
        ]);
    }

    public function edit(Supplier $supplier): Response
    {
        return Inertia::render('Inventory/Suppliers/Edit', [
            'supplier' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'contact_person' => $supplier->contact_person,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'address' => $supplier->address,
                'notes' => $supplier->notes,
                'is_active' => (bool) $supplier->is_active,
            ],
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $this->suppliers->update($supplier, $request->validated());

        return redirect()->route('inventory.suppliers.show', $supplier)->with('success', 'Supplier updated.');
    }
}
