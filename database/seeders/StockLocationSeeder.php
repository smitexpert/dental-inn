<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\StockLocationType;
use App\Models\StockLocation;
use Illuminate\Database\Seeder;

class StockLocationSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent: only create the default location if the table is empty.
        if (StockLocation::query()->exists()) {
            return;
        }

        StockLocation::create([
            'name' => 'Main Storage',
            'location_type' => StockLocationType::Storage,
            'is_active' => true,
        ]);
    }
}
