<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained('stock_locations')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamp('last_counted_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
