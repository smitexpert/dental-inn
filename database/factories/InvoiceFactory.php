<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 1000);
        $tax = 0;
        $discount = 0;
        $total = $subtotal - $discount + $tax;

        return [
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
            'patient_id' => Patient::factory(),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => InvoiceStatus::Draft->value,
            'subtotal' => $subtotal,
            'tax_rate' => 0,
            'tax_amount' => $tax,
            'discount_type' => DiscountType::None->value,
            'discount_value' => 0,
            'discount_amount' => $discount,
            'total' => $total,
            'paid_amount' => 0,
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }

    public function issued(): static
    {
        return $this->state(fn (): array => ['status' => InvoiceStatus::Issued->value]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => ['status' => InvoiceStatus::Paid->value]);
    }
}
