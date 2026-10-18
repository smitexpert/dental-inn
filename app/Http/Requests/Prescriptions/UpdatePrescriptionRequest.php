<?php

declare(strict_types=1);

namespace App\Http\Requests\Prescriptions;

use App\Enums\DosageFrequency;
use App\Enums\MealRelation;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-prescriptions');
    }

    public function rules(): array
    {
        $frequencies = array_map(fn ($c) => $c->value, DosageFrequency::cases());
        $meals = array_map(fn ($c) => $c->value, MealRelation::cases());

        return [
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id'],
            'general_advice' => ['nullable', 'string', 'max:5000'],
            'diagnosis_text' => ['nullable', 'string', 'max:5000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['nullable', 'integer', 'exists:medicines,id'],
            'items.*.medicine_name' => ['required', 'string', 'max:255'],
            'items.*.strength' => ['nullable', 'string', 'max:64'],
            'items.*.dosage' => ['required', 'string', 'max:64'],
            'items.*.frequency' => ['required', 'string', 'in:'.implode(',', $frequencies)],
            'items.*.frequency_label_bn' => ['nullable', 'string', 'max:128'],
            'items.*.duration' => ['nullable', 'string', 'max:64'],
            'items.*.meal_relation' => ['required', 'string', 'in:'.implode(',', $meals)],
            'items.*.meal_relation_label_bn' => ['nullable', 'string', 'max:128'],
            'items.*.instructions' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ];
    }
}
