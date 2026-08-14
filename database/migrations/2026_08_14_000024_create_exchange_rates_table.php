<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('from_currency_code', 3);
            $table->string('to_currency_code', 3);
            $table->decimal('rate', 18, 8);
            $table->dateTime('effective_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('from_currency_code')->references('code')->on('currencies')->cascadeOnDelete();
            $table->foreign('to_currency_code')->references('code')->on('currencies')->cascadeOnDelete();

            $table->index(['from_currency_code', 'to_currency_code', 'effective_at'], 'idx_rates_pair_effective');
            $table->index(['to_currency_code', 'from_currency_code', 'effective_at'], 'idx_rates_inverse_pair_effective');
            $table->index('effective_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
