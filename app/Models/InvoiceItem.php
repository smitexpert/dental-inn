<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property string $description
 * @property string $quantity
 * @property string $unit_price
 * @property string $line_total
 * @property ?int $product_id
 * @property ?int $treatment_record_id
 * @property int $sort_order
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 * @property-read Invoice $invoice
 * @property-read ?Product $product
 * @property-read ?TreatmentRecord $treatmentRecord
 */
class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
        'product_id',
        'treatment_record_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<TreatmentRecord, $this> */
    public function treatmentRecord(): BelongsTo
    {
        return $this->belongsTo(TreatmentRecord::class);
    }
}
