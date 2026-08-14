<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\Contracts\ProductRepository;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly ProductRepository $products) {}

    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'todays_appointments' => ['value' => 0, 'label' => "Today's appointments", 'hint' => 'Resets daily'],
                'pending_payments' => ['value' => 0, 'label' => 'Pending payments', 'hint' => 'Awaiting settlement'],
                'low_stock_items' => [
                    'value' => $this->products->lowStockCount(),
                    'label' => 'Low stock items',
                    'hint' => 'Below reorder threshold',
                ],
                'patients_today' => ['value' => 0, 'label' => 'New patients', 'hint' => 'This month'],
                'month_revenue' => ['value' => 0, 'label' => 'Revenue (this month)', 'hint' => 'Net of refunds'],
                'outstanding_balance' => ['value' => 0, 'label' => 'Outstanding balance', 'hint' => 'Across all patients'],
            ],
        ]);
    }
}
