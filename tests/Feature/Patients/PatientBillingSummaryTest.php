<?php

declare(strict_types=1);

namespace Tests\Feature\Patients;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Repositories\Contracts\PatientRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientBillingSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'view-patients', 'guard_name' => 'web']));
    }

    public function test_show_page_exposes_billing_summary_for_patient_with_open_and_paid_invoices(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();

        // Invoice A: 200 total, 50 paid → 150 still owed (open).
        Invoice::factory()->create([
            'patient_id' => $patient->id,
            'status' => InvoiceStatus::PartiallyPaid->value,
            'total' => 200,
            'paid_amount' => 50,
        ]);
        // Invoice B: 100 total, 100 paid → fully settled.
        Invoice::factory()->create([
            'patient_id' => $patient->id,
            'status' => InvoiceStatus::Paid->value,
            'total' => 100,
            'paid_amount' => 100,
        ]);
        // Different patient — must NOT contribute to the totals.
        $other = Patient::factory()->create();
        Invoice::factory()->create([
            'patient_id' => $other->id,
            'status' => InvoiceStatus::Issued->value,
            'total' => 999,
            'paid_amount' => 0,
        ]);

        $this->actingAs($admin)->get("/patients/{$patient->id}")
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Patients/Show')
                    ->where('billing.total_invoiced', '300.00')
                    ->where('billing.total_paid', '150.00')
                    ->where('billing.balance_due', '150.00')
                    ->where('billing.open_invoices', 1)
                    ->where('billing.invoice_count', 2)
                    ->etc(),
            );
    }

    public function test_show_page_handles_patient_with_no_invoices(): void
    {
        $admin = User::factory()->create(['is_active' => true])->assignRole('admin');
        $patient = Patient::factory()->create();

        $this->actingAs($admin)->get("/patients/{$patient->id}")
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Patients/Show')
                    ->where('billing.total_invoiced', '0.00')
                    ->where('billing.total_paid', '0.00')
                    ->where('billing.balance_due', '0.00')
                    ->where('billing.open_invoices', 0)
                    ->where('billing.invoice_count', 0)
                    ->etc(),
            );
    }

    public function test_repository_billing_summary_method_directly(): void
    {
        $patient = Patient::factory()->create();

        Invoice::factory()->create([
            'patient_id' => $patient->id,
            'status' => InvoiceStatus::Issued->value,
            'total' => 75,
            'paid_amount' => 25,
        ]);
        Invoice::factory()->create([
            'patient_id' => $patient->id,
            'status' => InvoiceStatus::Paid->value,
            'total' => 25,
            'paid_amount' => 25,
        ]);

        $repo = app(PatientRepository::class);
        $summary = $repo->billingSummary($patient);

        $this->assertSame('100.00', $summary['total_invoiced']);
        $this->assertSame('50.00', $summary['total_paid']);
        $this->assertSame('50.00', $summary['balance_due']);
        $this->assertSame(1, $summary['open_invoices']);
        $this->assertSame(2, $summary['invoice_count']);
    }
}
