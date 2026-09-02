<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->date('entry_date');
            $table->string('description');
            $table->foreignId('debit_account_id')->constrained('accounts');
            $table->foreignId('credit_account_id')->constrained('accounts');
            $table->decimal('amount', 18, 4);
            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 18, 8)->default(1.0);
            $table->nullableMorphs('reference');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('entry_date');
            $table->index(['debit_account_id', 'entry_date']);
            $table->index(['credit_account_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
