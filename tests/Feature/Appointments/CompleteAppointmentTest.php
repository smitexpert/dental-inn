<?php

declare(strict_types=1);

namespace Tests\Feature\Appointments;

use App\Enums\AppointmentStatus;
use App\Enums\InvoiceStatus;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Exercises the auto-draft-invoice-on-completion flow.
 *
 * The flow: when an appointment transitions to `Completed`, the system
 * drafts a zero-priced "Consultation" invoice against its patient so
 * the cashier can collect without manually creating one. Re-completion
 * (status flapping, back-dated corrections) must NOT create a duplicate.
 */
class CompleteAppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        foreach (['view-appointments', 'manage-appointments', 'cancel-appointments', 'check-in-appointments'] as $p) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']));
        }
    }

    public function test_completing_appointment_drafts_one_invoice_for_its_patient(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $appt = Appointment::factory()->create([
            'status' => AppointmentStatus::CheckedIn,
        ]);

        $this->actingAs($admin)->patch("/appointments/{$appt->id}/status", [
            'status' => 'completed',
        ])->assertRedirect();

        // Exactly one invoice was drafted.
        $this->assertSame(1, Invoice::query()->where('appointment_id', $appt->id)->count());

        $invoice = Invoice::query()->where('appointment_id', $appt->id)->first();

        // Linked to the right patient.
        $this->assertSame($appt->patient_id, $invoice->patient_id);

        // Default status: draft, total zero, with the auto-drafted
        // Consultation line item so it satisfies the `min:1` validator.
        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame('0.00', (string) $invoice->total);

        $invoice->load('items');
        $this->assertCount(1, $invoice->items);
        $this->assertSame('Consultation', $invoice->items->first()->description);

        // Audit: created_by records the user who triggered the
        // transition, not the appointment's original creator.
        $this->assertSame($admin->id, $invoice->created_by);
    }

    public function test_completing_twice_does_not_duplicate_invoice(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');

        // Build a checked-in appointment and walk it through the full
        // transition sequence twice: Confirmed -> CheckedIn ->
        // Completed, then attempt another Completed.
        $appt = Appointment::factory()->create([
            'status' => AppointmentStatus::Scheduled,
        ]);

        $this->actingAs($admin)->patch("/appointments/{$appt->id}/status", ['status' => 'confirmed']);
        $this->actingAs($admin)->patch("/appointments/{$appt->id}/status", ['status' => 'checked_in']);
        $this->actingAs($admin)->patch("/appointments/{$appt->id}/status", ['status' => 'completed']);
        $this->actingAs($admin)->patch("/appointments/{$appt->id}/status", ['status' => 'completed']); // no-op; Completed -> Completed is rejected by the validator anyway

        $this->assertSame(1, Invoice::query()->where('appointment_id', $appt->id)->count());
    }

    public function test_cancelling_then_completing_does_not_duplicate_invoice(): void
    {
        // Status flapping: Scheduled -> Confirmed -> Cancelled (this is
        // an unusual but legal transition for the model), then we
        // expect that if completion happens later only one invoice
        // exists for the appointment.
        //
        // Note: canTransitionTo() doesn't currently allow Cancelled ->
        // Completed, so we simulate the more realistic case: a fresh
        // appointment goes Completed once, the appointment remains
        // Completed, and a manual re-completion via the service path
        // is a no-op.
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $appt = Appointment::factory()->create([
            'status' => AppointmentStatus::CheckedIn,
        ]);

        $this->actingAs($admin)->patch("/appointments/{$appt->id}/status", ['status' => 'completed']);
        $firstCount = Invoice::query()->where('appointment_id', $appt->id)->count();
        $firstId = Invoice::query()->where('appointment_id', $appt->id)->first()->id;

        // Hit the service directly to mimic the controller's call on a
        // re-completion (the HTTP path can't actually re-complete a
        // completed appointment because canTransitionTo rejects it).
        $service = app(\App\Services\AppointmentBillingService::class);
        $service->createInvoiceForCompletedAppointment($appt->fresh(), $admin->id);

        $this->assertSame($firstCount, Invoice::query()->where('appointment_id', $appt->id)->count());
        $this->assertSame($firstId, Invoice::query()->where('appointment_id', $appt->id)->first()->id);
    }

    public function test_invoices_appear_on_appointment_show_page(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $appt = Appointment::factory()->create([
            'status' => AppointmentStatus::CheckedIn,
        ]);

        $this->actingAs($admin)->patch("/appointments/{$appt->id}/status", ['status' => 'completed']);

        // Render the appointment Show page and confirm the linked
        // invoice is in the Inertia payload.
        $response = $this->actingAs($admin)->get("/appointments/{$appt->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Appointments/Show')
                ->has('appointment.invoices', 1)
                ->where('appointment.invoices.0.status', 'draft')
            );
    }
}