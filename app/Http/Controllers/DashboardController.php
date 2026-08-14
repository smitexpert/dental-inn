<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Models\Appointment;
use App\Models\CashDrawer;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Repositories\Contracts\ProductRepository;
use App\Services\ExchangeRateService;
use App\Settings\ClinicSettings;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ExchangeRateService $rates,
    ) {}

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

        $notificationsToday = NotificationLog::query()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

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
                'cash_balance' => [
                    'value' => $this->cashBalanceInBase(),
                    'label' => 'Cash drawer',
                    'hint' => sprintf('Total in %s', app(ClinicSettings::class)->currency_code),
                ],
                'notifications_today' => [
                    'value' => $notificationsToday,
                    'label' => 'Notifications sent',
                    'hint' => 'SMS, WhatsApp, email',
                ],
            ],
        ]);
    }

    private function cashBalanceInBase(): string
    {
        $base = app(ClinicSettings::class)->currency_code;
        $drawers = CashDrawer::query()->with('currency')->get();
        $total = 0.0;

        foreach ($drawers as $drawer) {
            $code = (string) $drawer->currency_code;
            $balance = (float) $drawer->balance;
            if ($balance == 0.0) {
                continue;
            }

            $rate = $this->rates->getRateOrNull($code, $base);
            $multiplier = $rate !== null ? (float) $rate->rate : 0.0;
            $total += $balance * $multiplier;
        }

        return number_format($total, 2, '.', '');
    }
}
