<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicine>
 */
class MedicineFactory extends Factory
{
    public function definition(): array
    {
        $samples = [
            ['name' => 'Aceclofenac', 'strength' => '100mg', 'form' => 'Tablet', 'name_bn' => 'এসিক্লোফেনাক'],
            ['name' => 'Paracetamol', 'strength' => '500mg', 'form' => 'Tablet', 'name_bn' => 'প্যারাসিটামল'],
            ['name' => 'Amoxicillin', 'strength' => '500mg', 'form' => 'Capsule', 'name_bn' => 'অ্যামক্সিসিলিন'],
            ['name' => 'Metronidazole', 'strength' => '400mg', 'form' => 'Tablet', 'name_bn' => 'মেট্রোনিডাজল'],
            ['name' => 'Ibuprofen', 'strength' => '400mg', 'form' => 'Tablet', 'name_bn' => 'ইবুপ্রোফেন'],
            ['name' => 'Cetirizine', 'strength' => '10mg', 'form' => 'Tablet', 'name_bn' => 'সিটিরিজিন'],
            ['name' => 'Cefixime', 'strength' => '200mg', 'form' => 'Capsule', 'name_bn' => 'সেফিক্সিম'],
        ];

        $sample = fake()->randomElement($samples);

        return [
            'name' => $sample['name'],
            'strength' => $sample['strength'],
            'form' => $sample['form'],
            'name_bn' => $sample['name_bn'],
            'is_active' => true,
        ];
    }
}