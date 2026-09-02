<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->string('type', 16);
            $table->integer('quantity');
            $table->string('reason', 255)->nullable();
            $table->string('reference_type', 64)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->decimal('unit_cost', 18, 4)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['product_id', 'created_at']);
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
