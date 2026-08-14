<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_movements', function (Blueprint $table): void {
            $table->id();
            $table->string('currency_code', 3);
            $table->string('type', 20);
            $table->decimal('amount', 18, 4);
            $table->foreignId('exchange_rate_id')->nullable()->constrained('exchange_rates')->nullOnDelete();
            $table->string('counter_currency_code', 3)->nullable();
            $table->decimal('counter_amount', 18, 4)->nullable();
            $table->string('reason')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->string('exchange_group_id', 36)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('currency_code')->references('code')->on('currencies')->cascadeOnDelete();
            $table->foreign('counter_currency_code')->references('code')->on('currencies')->nullOnDelete();

            $table->index(['currency_code', 'created_at'], 'idx_cash_movements_currency_date');
            $table->index(['reference_type', 'reference_id'], 'idx_cash_movements_reference');
            $table->index('type');
            $table->index('exchange_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
