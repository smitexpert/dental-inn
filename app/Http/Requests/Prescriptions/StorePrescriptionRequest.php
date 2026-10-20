<?php

declare(strict_types=1);

namespace App\Http\Requests\Prescriptions;

use App\Enums\DosageFrequency;
use App\Enums\MealRelation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePrescriptionRequest extends FormRequest
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
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'treatment_record_id' => ['nullable', 'integer', 'exists:treatment_records,id'],
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

    /**
     * Bangla-friendly labels so the doctor sees what is missing instead of
     * an English `items.0.dosage` field reference.
     */
    public function attributes(): array
    {
        return [
            'doctor_id' => 'ডাক্তার',
            'diagnosis_text' => 'রোগ নির্ণয়',
            'general_advice' => 'সাধারণ পরামর্শ',
            'items' => 'ওষুধের তালিকা',
            'items.*.medicine_name' => 'ওষুধের নাম',
            'items.*.dosage' => 'কতটুকু (Dosage)',
            'items.*.frequency' => 'কতবার (Frequency)',
            'items.*.meal_relation' => 'খাবারের সাথে (Meal relation)',
            'items.*.quantity' => 'পরিমাণ (Qty)',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute ফিল্ডটি আবশ্যক।',
            'string' => ':attribute অবশ্যই একটি টেক্সট হতে হবে।',
            'integer' => ':attribute অবশ্যই একটি সংখ্যা হতে হবে।',
            'min' => [
                'array' => 'কমপক্ষে একটি ওষুধ যোগ করুন।',
                'integer' => ':attribute এর মান কমপক্ষে :min হতে হবে।',
            ],
            'exists' => 'নির্বাচিত :attribute সিস্টেমে পাওয়া যায়নি।',
            'in' => 'নির্বাচিত :attribute অবৈধ।',
            'max' => [
                'string' => ':attribute এর দৈর্ঘ্য সর্বোচ্চ :max অক্ষর হতে পারে।',
                'integer' => ':attribute এর মান সর্বোচ্চ :max হতে পারে।',
            ],
        ];
    }

    /**
     * Attach a top-level summary so the UI can render one combined banner
     * instead of forcing the doctor to scan each row.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($v->errors()->isNotEmpty()) {
                $v->errors()->add(
                    '_summary',
                    'প্রেসক্রিপশন সংরক্ষণ করা যায়নি — নিচের ত্রুটিগুলো ঠিক করে আবার চেষ্টা করুন।',
                );
            }
        });
    }
}
