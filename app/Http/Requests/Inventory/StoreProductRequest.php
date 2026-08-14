<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\ProductCategory;
use App\Enums\ProductUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
        $categories = array_map(fn (ProductCategory $c) => $c->value, ProductCategory::cases());
        $units = array_map(fn (ProductUnit $u) => $u->value, ProductUnit::cases());

        return [
            'sku' => ['required', 'string', 'max:64', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:64', 'unique:products,barcode'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in($categories)],
            'unit' => ['required', Rule::in($units)],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'reorder_quantity' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'track_stock' => ['nullable', 'boolean'],
        ];
    }
}
