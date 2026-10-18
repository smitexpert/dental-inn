<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained('medicines')->nullOnDelete();
            $table->string('medicine_name');
            $table->string('strength')->nullable();
            $table->string('dosage');
            $table->string('frequency', 32);
            $table->string('frequency_label_bn')->nullable();
            $table->string('duration')->nullable();
            $table->string('meal_relation', 32);
            $table->string('meal_relation_label_bn')->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['prescription_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};