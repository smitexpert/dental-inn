<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StoreInvoiceRequest;
use App\Http\Requests\Billing\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepository;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceRepository $invoices,
        private readonly InvoiceService $service,
    ) {}

    public function index(): Response
    {
        $page = $this->invoices->paginate(
            request()->integer('per_page', 25),
            request()->only(['q', 'status', 'patient_id', 'date_from', 'date_to']),
        );

        return Inertia::render('Billing/Invoices/Index', [
            'invoices' => $page,
            'filters' => request()->only(['q', 'status', 'patient_id', 'date_from', 'date_to']),
            'statuses' => collect(InvoiceStatus::cases())->map(fn (InvoiceStatus $s): array => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Billing/Invoices/Create', [
            'invoice_number' => $this->service->nextInvoiceNumber(),
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = InvoiceStatus::Draft->value;
        $data['created_by'] = $request->user()->id;
        $data['invoice_number'] = $this->service->nextInvoiceNumber();

        $invoice = new Invoice;
        $invoice->fill(array_diff_key($data, array_flip(['items'])));
        $invoice->save();

        $totals = $this->totals($data);
        $items = $this->items($data['items']);

        $this->service->save($invoice->fresh(), $items, $totals);

        return redirect()->route('billing.invoices.show', $invoice)->with('success', 'Invoice saved as draft.');
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->load(['patient', 'creator', 'items', 'payments.receiver']);

        return Inertia::render('Billing/Invoices/Show', [
            'invoice' => $this->serialize($invoice),
        ]);
    }

    public function edit(Invoice $invoice): RedirectResponse|Response
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            return redirect()->route('billing.invoices.show', $invoice)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $invoice->load('items');

        return Inertia::render('Billing/Invoices/Edit', [
            'invoice' => $this->serialize($invoice),
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            return redirect()->route('billing.invoices.show', $invoice)
                ->with('error', 'Only draft invoices can be updated.');
        }

        $data = $request->validated();
        $invoice->fill(array_diff_key($data, array_flip(['items'])));
        $invoice->save();

        $totals = $this->totals($data);
        $items = $this->items($data['items']);

        $this->service->save($invoice->fresh(), $items, $totals);

        return redirect()->route('billing.invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function issue(Request $request, Invoice $invoice): RedirectResponse
    {
        try {
            $this->service->issue($invoice, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }

        return redirect()->route('billing.invoices.show', $invoice)->with('success', 'Invoice issued.');
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        try {
            $this->service->cancel($invoice);
        } catch (RuntimeException $e) {
            return back()->withErrors(['invoice' => $e->getMessage()]);
        }

        return redirect()->route('billing.invoices.show', $invoice)->with('success', 'Invoice cancelled.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function totals(array $data): array
    {
        $subtotal = 0.0;
        foreach ($data['items'] as $row) {
            $subtotal += (float) $row['quantity'] * (float) $row['unit_price'];
        }

        $discountType = DiscountType::from((string) ($data['discount_type'] ?? DiscountType::None->value));
        $discountValue = (float) ($data['discount_value'] ?? 0);

        $discountAmount = match ($discountType) {
            DiscountType::Percentage => round($subtotal * ($discountValue / 100), 2),
            DiscountType::Fixed => min($discountValue, $subtotal),
            DiscountType::None => 0.0,
        };

        $taxRate = (float) ($data['tax_rate'] ?? 0);
        $taxableBase = max(0.0, $subtotal - $discountAmount);
        $taxAmount = round($taxableBase * ($taxRate / 100), 2);

        $total = round($taxableBase + $taxAmount, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount_type' => $discountType->value,
            'discount_value' => $discountValue,
            'discount_amount' => $discountAmount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function items(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $qty = (float) $row['quantity'];
            $price = (float) $row['unit_price'];
            $out[] = [
                'description' => (string) $row['description'],
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => round($qty * $price, 2),
                'product_id' => $row['product_id'] ?? null,
                'treatment_record_id' => $row['treatment_record_id'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Invoice $invoice): array
    {
        $invoice->refresh();

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'patient' => $invoice->patient ? [
                'id' => $invoice->patient->id,
                'patient_code' => $invoice->patient->patient_code,
                'name' => $invoice->patient->name,
            ] : null,
            'creator' => $invoice->creator ? [
                'id' => $invoice->creator->id,
                'name' => $invoice->creator->name,
            ] : null,
            'invoice_date' => $invoice->invoice_date->toDateString(),
            'due_date' => $invoice->due_date->toDateString(),
            'status' => $invoice->status->value,
            'status_label' => $invoice->status->label(),
            'subtotal' => (string) $invoice->subtotal,
            'tax_rate' => (string) $invoice->tax_rate,
            'tax_amount' => (string) $invoice->tax_amount,
            'discount_type' => $invoice->discount_type->value,
            'discount_value' => (string) $invoice->discount_value,
            'discount_amount' => (string) $invoice->discount_amount,
            'total' => (string) $invoice->total,
            'paid_amount' => (string) $invoice->paid_amount,
            'balance_due' => $invoice->balance_due,
            'notes' => $invoice->notes,
            'issued_at' => $invoice->issued_at?->toIso8601String(),
            'items' => $invoice->items->map(fn ($i) => [
                'id' => $i->id,
                'description' => $i->description,
                'quantity' => (string) $i->quantity,
                'unit_price' => (string) $i->unit_price,
                'line_total' => (string) $i->line_total,
                'product_id' => $i->product_id,
                'treatment_record_id' => $i->treatment_record_id,
            ])->all(),
            'payments' => $invoice->payments->map(fn ($p) => [
                'id' => $p->id,
                'payment_date' => $p->payment_date->toDateString(),
                'amount' => (string) $p->amount,
                'method' => $p->method->value,
                'method_label' => $p->method->label(),
                'reference' => $p->reference,
                'receiver' => $p->receiver?->name,
            ])->all(),
        ];
    }
}
