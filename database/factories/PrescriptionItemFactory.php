<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DosageFrequency;
use App\Enums\MealRelation;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrescriptionItem>
 */
class PrescriptionItemFactory extends Factory
{
    public function definition(): array
    {
        $frequency = fake()->randomElement(DosageFrequency::cases());
        $meal = fake()->randomElement(MealRelation::cases());

        return [
            'prescription_id' => Prescription::factory(),
            'medicine_id' => Medicine::factory(),
            'medicine_name' => 'Aceclofenac',
            'strength' => '100mg',
            'dosage' => '১ টি',
            'frequency' => $frequency->value,
            'frequency_label_bn' => $frequency->labelBn(),
            'duration' => '৫ দিন',
            'meal_relation' => $meal->value,
            'meal_relation_label_bn' => $meal->labelBn(),
            'instructions' => null,
            'quantity' => 1,
            'sort_order' => 0,
        ];
    }
}