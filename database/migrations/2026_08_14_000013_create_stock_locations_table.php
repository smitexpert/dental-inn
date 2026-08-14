<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 128);
            $table->string('location_type', 16);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('name');
            $table->index('location_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_locations');
    }
};
