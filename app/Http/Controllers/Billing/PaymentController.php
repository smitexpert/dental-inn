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
    ) {}

    public function index(): Response
    {
        $page = $this->payments->paginate(
            request()->integer('per_page', 25),
            request()->only(['q', 'method', 'date_from', 'date_to', 'invoice_id']),
        );

        return Inertia::render('Billing/Payments/Index', [
            'payments' => $page,
            'filters' => request()->only(['q', 'method', 'date_from', 'date_to', 'invoice_id']),
            'methods' => collect(PaymentMethod::cases())->map(fn (PaymentMethod $m): array => [
                'value' => $m->value,
                'label' => $m->label(),
            ])->all(),
        ]);
    }

    public function store(RecordPaymentRequest $request): RedirectResponse
    {
        try {
            $payment = $this->service->record($request->validated(), $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('billing.payments.index')->with('success', 'Payment recorded.');
    }

    public function receipt(Payment $payment): Response
    {
        $payment->load(['invoice.patient', 'receiver']);

        return Inertia::render('Billing/Payments/Receipt', [
            'payment' => [
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
                ] : null,
            ],
        ]);
    }
}
