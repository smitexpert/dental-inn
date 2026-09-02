<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\InvoiceService;
use App\Settings\ClinicSettings;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
    }

    public function test_next_invoice_number_increments_counter(): void
    {
        $service = app(InvoiceService::class);
        $first = $service->nextInvoiceNumber();
        $second = $service->nextInvoiceNumber();

        $this->assertSame('INV-0001', $first);
        $this->assertSame('INV-0002', $second);
        $this->assertSame(3, (int) app(ClinicSettings::class)->next_invoice_number);
    }

    public function test_save_recomputes_totals(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create([
            'invoice_date' => '2026-08-14',
            'due_date' => '2026-09-14',
        ]);

        $items = [
            ['description' => 'Cleaning', 'quantity' => 1, 'unit_price' => 100, 'line_total' => 100],
            ['description' => 'X-ray', 'quantity' => 2, 'unit_price' => 50, 'line_total' => 100],
        ];

        $totals = [
            'subtotal' => 200,
            'discount_type' => DiscountType::None->value,
            'discount_value' => 0,
            'discount_amount' => 0,
            'tax_rate' => 5,
            'tax_amount' => 10,
            'total' => 210,
        ];

        $service = app(InvoiceService::class);
        $saved = $service->save($invoice, $items, $totals);

        $this->assertSame('210.00', (string) $saved->total);
        $this->assertSame(2, $saved->items()->count());
    }

    public function test_issue_assigns_number_and_status(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Draft->value,
            'total' => 100,
        ]);

        $service = app(InvoiceService::class);
        $issued = $service->issue($invoice, $user->id);

        $this->assertSame(InvoiceStatus::Issued->value, $issued->status->value);
        $this->assertNotEmpty($issued->invoice_number);
        $this->assertNotNull($issued->issued_at);
        $this->assertSame(1, LedgerEntry::query()->where('reference_type', 'invoice')->count());
    }

    public function test_issue_rejects_non_draft(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::Paid->value,
            'total' => 100,
        ]);

        $this->expectException(\RuntimeException::class);
        app(InvoiceService::class)->issue($invoice, $user->id);
    }
}
