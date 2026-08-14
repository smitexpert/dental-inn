<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkInvoicesOverdueTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_past_due_issued_invoices(): void
    {
        $due = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'due_date' => '2026-01-01',
            'invoice_date' => '2025-12-15',
            'total' => 100,
        ]);
        $current = Invoice::factory()->create([
            'status' => InvoiceStatus::Issued->value,
            'due_date' => now()->addDays(7)->toDateString(),
            'total' => 100,
        ]);

        $this->artisan('invoices:mark-overdue', ['--today' => '2026-08-14'])
            ->assertExitCode(0);

        $due->refresh();
        $current->refresh();

        $this->assertSame(InvoiceStatus::Overdue->value, $due->status->value);
        $this->assertSame(InvoiceStatus::Issued->value, $current->status->value);
    }

    public function test_is_idempotent(): void
    {
        Invoice::factory()->create([
            'status' => InvoiceStatus::Overdue->value,
            'due_date' => '2026-01-01',
            'total' => 100,
        ]);

        $this->artisan('invoices:mark-overdue', ['--today' => '2026-08-14'])
            ->assertExitCode(0);

        $this->assertSame(1, Invoice::query()->where('status', InvoiceStatus::Overdue->value)->count());
    }
}
