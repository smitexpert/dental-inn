<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\RecordPaymentRequest;
use App\Models\Payment;
use App\Repositories\Contracts\InvoiceRepository;
use App\Repositories\Contracts\PaymentRepository;
use App\Services\PaymentService;
use App\Settings\ClinicSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly InvoiceRepository $invoices,
        private readonly PaymentService $service,
        private readonly ClinicSettings $settings,
    ) {}

    public function index(): Response
    {
        $filters = request()->only(['q', 'method', 'date_from', 'date_to', 'invoice_id']);
        $page = $this->payments->paginate(request()->integer('per_page', 25), $filters);
        $aggregates = $this->payments->aggregatesFiltered($filters);

        // Outstanding computed across the FULL filter set (not just the current
        // page). Pull distinct invoice ids from the repo, then sum their open
        // balances via a single grouped query.
        $invoiceIds = $this->payments->distinctInvoiceIds($filters);

        $outstanding = 0.0;
        $invoiceCount = 0;
        if ($invoiceIds !== []) {
            $row = DB::table('invoices')
                ->whereIn('id', $invoiceIds)
                ->selectRaw('COALESCE(SUM(CAST(total AS DECIMAL(18,2)) - CAST(paid_amount AS DECIMAL(18,2))), 0) AS due,
                    COUNT(*) AS invoice_count')
                ->first();
            $outstanding = (float) ($row->due ?? 0);
            $invoiceCount = (int) ($row->invoice_count ?? 0);
        }

        $aggregates['outstanding'] = number_format($outstanding, 2, '.', '');
        $aggregates['distinct_invoices_seen'] = $invoiceCount;

        $kpis = [
            ['id' => 'today', 'label' => 'Total today',
                'value' => (string) Payment::whereDate('payment_date', today())->sum('amount'), 'format' => 'currency'],
            ['id' => 'month', 'label' => 'Total this month',
                'value' => (string) Payment::whereMonth('payment_date', now()->month)->sum('amount'), 'format' => 'currency'],
            ['id' => 'avg', 'label' => 'Avg ticket',
                'value' => (string) (Payment::whereMonth('payment_date', now()->month)->avg('amount') ?? '0'), 'format' => 'currency'],
            ['id' => 'count', 'label' => 'Count',
                'value' => (string) Payment::whereMonth('payment_date', now()->month)->count(), 'format' => 'number'],
        ];

        return Inertia::render('Billing/Payments/Index', [
            'payments' => $page,
            'filters' => $filters,
            'methods' => collect(PaymentMethod::cases())->map(fn (PaymentMethod $m): array => [
                'value' => $m->value,
                'label' => $m->label(),
            ])->all(),
            'aggregates' => $aggregates,
            'kpis' => $kpis,
        ]);
    }

    public function store(RecordPaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $settings = app(ClinicSettings::class);
        $data['currency_code'] ??= $settings->currency_code;
        $data['exchange_rate'] ??= 1.0;

        try {
            $this->service->record($data, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('billing.payments.index')->with('success', 'Payment recorded.');
    }

    public function receipt(Payment $payment): Response
    {
        $payment->load(['invoice.patient', 'receiver']);

        return Inertia::render('Billing/Payments/Receipt', [
            'payment' => $this->serializePayment($payment),
        ]);
    }

    /**
     * Dedicated printable A4 view for a payment receipt. Reuses the
     * same serializer as receipt() and pulls currency settings from
     * ClinicSettings so the printed page matches the rest of the app.
     * Intentionally has no letterhead or page chrome — the clinic's
     * stationery already carries the letterhead, and the print page
     * must not duplicate it.
     */
    public function print(Payment $payment): Response
    {
        $payment->load(['invoice.patient', 'receiver']);

        return Inertia::render('Billing/Payments/Print', [
            'payment' => $this->serializePayment($payment),
            'currency' => [
                'symbol' => $this->settings->currency_symbol,
                'code' => $this->settings->currency_code,
                'decimals' => $this->settings->currency_decimals,
                'position' => $this->settings->currency_position,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'payment_date' => $payment->payment_date->toDateString(),
            'amount' => (string) $payment->amount,
            'currency_code' => $payment->currency_code,
            'method' => $payment->method->value,
            'method_label' => $payment->method->label(),
            'reference' => $payment->reference,
            'notes' => $payment->notes,
            'receiver' => $payment->receiver?->name,
            'invoice' => $payment->invoice ? [
                'id' => $payment->invoice->id,
                'invoice_number' => $payment->invoice->invoice_number,
                'patient' => $payment->invoice->patient ? [
                    'patient_code' => $payment->invoice->patient->patient_code,
                    'name' => $payment->invoice->patient->name,
                ] : null,
                'total' => (string) $payment->invoice->total,
                'paid_amount' => (string) $payment->invoice->paid_amount,
                'balance_due' => $payment->invoice->balance_due,
                'show_url' => route('billing.invoices.show', $payment->invoice),
            ] : null,
        ];
    }
}
