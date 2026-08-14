<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 64);
            $table->string('barcode', 64)->nullable();
            $table->string('name');
            $table->string('category', 32);
            $table->string('unit', 16);
            $table->decimal('cost_price', 18, 2)->nullable();
            $table->decimal('selling_price', 18, 2)->nullable();
            $table->unsignedInteger('reorder_level')->default(0);
            $table->unsignedInteger('reorder_quantity')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('track_stock')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('sku');
            $table->unique('barcode');
            $table->index('name');
            $table->index('category');
            $table->index('is_active');
            $table->index('track_stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
