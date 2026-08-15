<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Settings\ClinicSettings;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Authoritative writer for invoices.
 *
 * `save()` recomputes all totals in a single transaction, replacing
 * line items wholesale. `nextInvoiceNumber()` increments the clinic
 * settings counter under a row-level lock so concurrent users don't
 * collide.
 */
class InvoiceService
{
    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function save(Invoice $invoice, array $items, array $totals): Invoice
    {
        return DB::transaction(function () use ($invoice, $items, $totals): Invoice {
            if (isset($totals['invoice_number'])) {
                $invoice->invoice_number = (string) $totals['invoice_number'];
            }

            $invoice->subtotal = (string) $totals['subtotal'];
            $invoice->discount_type = $totals['discount_type'] ?? DiscountType::None->value;
            $invoice->discount_value = (string) ($totals['discount_value'] ?? 0);
            $invoice->discount_amount = (string) $totals['discount_amount'];
            $invoice->tax_rate = (string) ($totals['tax_rate'] ?? 0);
            $invoice->tax_amount = (string) $totals['tax_amount'];
            $invoice->total = (string) $totals['total'];

            $invoice->save();

            $invoice->items()->delete();

            foreach ($items as $i => $row) {
                $invoice->items()->save(new InvoiceItem([
                    'description' => (string) $row['description'],
                    'quantity' => (string) $row['quantity'],
                    'unit_price' => (string) $row['unit_price'],
                    'line_total' => (string) $row['line_total'],
                    'product_id' => isset($row['product_id']) && $row['product_id'] !== null && $row['product_id'] !== ''
                        ? (int) $row['product_id']
                        : null,
                    'treatment_record_id' => isset($row['treatment_record_id']) && $row['treatment_record_id'] !== null && $row['treatment_record_id'] !== ''
                        ? (int) $row['treatment_record_id']
                        : null,
                    'sort_order' => $i,
                ]));
            }

            return $invoice->fresh(['items']);
        });
    }

    /**
     * Generate the next invoice number under a row lock on settings.
     */
    public function nextInvoiceNumber(): string
    {
        return DB::transaction(function (): string {
            $settings = app(ClinicSettings::class);

            $prefix = (string) $settings->invoice_prefix;
            $next = (int) $settings->next_invoice_number;

            $width = max(4, strlen((string) $next));
            $number = $prefix.str_pad((string) $next, $width, '0', STR_PAD_LEFT);

            // Bump the counter; the row is locked for the duration of
            // this transaction so concurrent calls see a unique value.
            $settings->next_invoice_number = $next + 1;
            $settings->save();

            return $number;
        });
    }

    /**
     * Issue an invoice: set status + issued_at, allocate the number,
     * and post the ledger entry. Caller is expected to have already
     * saved the invoice with totals.
     */
    public function issue(Invoice $invoice, int $userId): Invoice
    {
        if ($invoice->status !== InvoiceStatus::Draft) {
            throw new RuntimeException('Only draft invoices can be issued.');
        }

        if ($invoice->invoice_number === '' || $invoice->invoice_number === null) {
            $invoice->invoice_number = $this->nextInvoiceNumber();
        }

        $invoice->status = InvoiceStatus::Issued;
        $invoice->issued_at = now();
        $invoice->save();

        $this->ledger->recordInvoiceIssue($invoice->fresh(), $userId);

        return $invoice->fresh();
    }

    public function cancel(Invoice $invoice): Invoice
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw new RuntimeException('Cannot cancel a paid invoice.');
        }

        if ((float) $invoice->paid_amount > 0) {
            throw new RuntimeException('Cannot cancel an invoice that has payments recorded.');
        }

        $invoice->status = InvoiceStatus::Cancelled;
        $invoice->save();

        return $invoice->fresh();
    }
}
