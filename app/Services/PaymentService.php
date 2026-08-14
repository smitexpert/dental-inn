<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Writes payments and updates the parent invoice.
 *
 * Every call wraps (1) Payment insert, (2) invoice.paid_amount bump,
 * (3) status recompute, (4) LedgerService::recordPaymentReceived in a
 * single DB transaction. Failure rolls everything back.
 */
class PaymentService
{
    public function __construct(private readonly LedgerService $ledger) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, int $userId): Payment
    {
        $invoice = Invoice::query()->findOrFail((int) $data['invoice_id']);

        if (in_array($invoice->status, [InvoiceStatus::Cancelled->value, InvoiceStatus::Refunded->value, InvoiceStatus::Draft->value], true)) {
            throw new RuntimeException('Payments can only be recorded on issued or partially-paid invoices.');
        }

        $amount = (float) $data['amount'];
        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be positive.');
        }

        $newPaid = (float) $invoice->paid_amount + $amount;
        if ($newPaid > ((float) $invoice->total + 0.0001)) {
            throw new RuntimeException('Payment would exceed invoice total.');
        }

        return DB::transaction(function () use ($data, $invoice, $newPaid, $userId): Payment {
            $payment = new Payment($data);
            $payment->received_by = $userId;
            $payment->created_at = now();
            $payment->save();

            $invoice->paid_amount = $newPaid;

            if ((float) $invoice->total > 0 && $newPaid >= (float) $invoice->total) {
                $invoice->status = InvoiceStatus::Paid;
            } elseif ($newPaid > 0) {
                $invoice->status = InvoiceStatus::PartiallyPaid;
            }

            $invoice->save();

            $this->ledger->recordPaymentReceived($payment->fresh(), '1000', '1010', $userId);

            return $payment->fresh();
        });
    }
}
