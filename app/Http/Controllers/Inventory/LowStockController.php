<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ProductRepository;
use Inertia\Inertia;
use Inertia\Response;

class LowStockController extends Controller
{
    public function __construct(private readonly ProductRepository $products) {}

    public function index(): Response
    {
        $page = $this->products->lowStockQuery()
            ->paginate(request()->integer('per_page', 25))
            ->withQueryString();

        return Inertia::render('Inventory/LowStock', [
            'products' => $page,
        ]);
    }
}
