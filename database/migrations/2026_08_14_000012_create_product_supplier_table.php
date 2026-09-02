<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_supplier', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_sku', 64)->nullable();
            $table->decimal('unit_cost', 18, 4)->nullable();
            $table->unsignedInteger('lead_time_days')->nullable();
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'supplier_id']);
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
