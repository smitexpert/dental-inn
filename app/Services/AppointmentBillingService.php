<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Appointment;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Glue between the appointment lifecycle and the billing subsystem.
 *
 * The clinic wants every completed appointment to produce a draft
 * invoice so the cashier can collect against the visit without having
 * to remember which doctor saw which patient. This service is the
 * only place that policy lives.
 *
 * Idempotency is the central concern: an appointment can be Completed
 * more than once (status flapping, back-dated corrections, the
 * controller retrying after a transient error), and a single
 * appointment must end up with at most one auto-drafted invoice per
 * visit. We guard with `invoices()->exists()` so re-completion returns
 * the existing row.
 */
class AppointmentBillingService
{
    public function __construct(private readonly InvoiceService $invoices) {}

    /**
     * Draft a zero-priced "Consultation" invoice for a completed
     * appointment. Returns the existing invoice if one was already
     * auto-drafted for this appointment, so callers can safely invoke
     * this on every Completed transition without dedup themselves.
     *
     * Returns `null` if the appointment has no patient (defensive —
     * should never happen given the FK, but we don't want to crash
     * the status controller over it).
     */
    public function createInvoiceForCompletedAppointment(Appointment $appointment, int $userId): ?Invoice
    {
        $patientId = $appointment->patient_id;
        if ($patientId === null) {
            return null;
        }

        // Idempotency guard: if an invoice already references this
        // appointment, return it instead of creating a duplicate.
        // `first()` runs after a fresh query so a stale relation cache
        // on the model can't mask a real second invoice.
        $existing = Invoice::query()->where('appointment_id', $appointment->id)->first();
        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($appointment, $userId): Invoice {
            $invoice = new Invoice([
                'invoice_number' => $this->invoices->nextInvoiceNumber(),
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'invoice_date' => Carbon::now()->toDateString(),
                // Default 14-day terms. The cashier can extend this
                // when issuing the invoice; auto-drafted invoices
                // are Draft status until then.
                'due_date' => Carbon::now()->addDays(14)->toDateString(),
                'status' => InvoiceStatus::Draft->value,
                'discount_type' => DiscountType::None->value,
                'notes' => sprintf(
                    'Auto-drafted from appointment #%d on %s.',
                    $appointment->id,
                    Carbon::now()->toDateTimeString(),
                ),
                'created_by' => $userId,
            ]);
            $invoice->save();

            // Single zero-priced line item so the invoice passes the
            // `items|min:1` validation rule. The cashier edits this on
            // the draft invoice to capture the real consultation fee
            // (and any procedures) before issuing.
            $this->invoices->save($invoice, [
                [
                    'description' => 'Consultation',
                    'quantity' => 1.0,
                    'unit_price' => 0.0,
                    'line_total' => 0.0,
                    'product_id' => null,
                    'treatment_record_id' => null,
                ],
            ], [
                'subtotal' => 0.0,
                'discount_type' => DiscountType::None->value,
                'discount_value' => 0.0,
                'discount_amount' => 0.0,
                'tax_rate' => 0.0,
                'tax_amount' => 0.0,
                'total' => 0.0,
            ]);

            return $invoice->fresh(['items']);
        });
    }
}
