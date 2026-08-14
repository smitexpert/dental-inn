<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Repositories\Contracts\ProductRepository;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly ProductRepository $products) {}

    public function index(): Response
    {
        $today = now()->toDateString();

        $todaysAppointments = Appointment::query()
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value])
            ->count();

        $pendingPayments = Invoice::query()
            ->whereIn('status', [
                InvoiceStatus::Issued->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ])
            ->count();

        $monthRevenue = (float) Invoice::query()
            ->whereIn('status', [InvoiceStatus::Paid->value, InvoiceStatus::PartiallyPaid->value])
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('paid_amount');

        $openInvoices = Invoice::query()->whereIn('status', [
            InvoiceStatus::Issued->value,
            InvoiceStatus::PartiallyPaid->value,
            InvoiceStatus::Overdue->value,
        ])->get();

        $outstandingBalance = (float) $openInvoices->sum(fn (Invoice $i) => (float) $i->total - (float) $i->paid_amount);

        return Inertia::render('Dashboard', [
            'stats' => [
                'todays_appointments' => [
                    'value' => $todaysAppointments,
                    'label' => "Today's appointments",
                    'hint' => 'Resets daily',
                ],
                'pending_payments' => [
                    'value' => $pendingPayments,
                    'label' => 'Pending payments',
                    'hint' => 'Awaiting settlement',
                ],
                'low_stock_items' => [
                    'value' => $this->products->lowStockCount(),
                    'label' => 'Low stock items',
                    'hint' => 'Below reorder threshold',
                ],
                'patients_today' => ['value' => 0, 'label' => 'New patients', 'hint' => 'This month'],
                'month_revenue' => [
                    'value' => number_format($monthRevenue, 2, '.', ''),
                    'label' => 'Revenue (this month)',
                    'hint' => 'Net of refunds',
                ],
                'outstanding_balance' => [
                    'value' => number_format($outstandingBalance, 2, '.', ''),
                    'label' => 'Outstanding balance',
                    'hint' => 'Across all patients',
                ],
            ],
        ]);
    }
}
