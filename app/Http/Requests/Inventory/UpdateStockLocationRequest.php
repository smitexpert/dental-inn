<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\StockLocationType;
use App\Models\StockLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-products');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var StockLocation|null $location */
        $location = $this->route('location');
        $locationId = $location?->id;

        $types = array_map(fn (StockLocationType $t) => $t->value, StockLocationType::cases());

        return [
            'name' => ['required', 'string', 'max:128', Rule::unique('stock_locations', 'name')->ignore($locationId)],
            'location_type' => ['required', Rule::in($types)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
