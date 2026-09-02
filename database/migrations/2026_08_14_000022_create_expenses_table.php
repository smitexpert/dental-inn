<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->date('expense_date');
            $table->foreignId('category_id')->constrained('expense_categories');
            $table->decimal('amount', 18, 2);
            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 18, 8)->default(1.0);
            $table->string('payment_method', 20);
            $table->string('vendor')->nullable();
            $table->text('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status', 16)->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('expense_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
