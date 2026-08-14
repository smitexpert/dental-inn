<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_drawers', function (Blueprint $table): void {
            $table->id();
            $table->string('currency_code', 3)->unique();
            $table->decimal('balance', 18, 4)->default(0);
            $table->dateTime('last_reconciled_at')->nullable();
            $table->decimal('last_reconciled_balance', 18, 4)->nullable();
            $table->timestamps();

            $table->foreign('currency_code')->references('code')->on('currencies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_drawers');
    }
};
