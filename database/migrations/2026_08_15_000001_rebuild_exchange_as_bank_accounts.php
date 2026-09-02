<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Drop the legacy tables in FK order.
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_drawers');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');

        // 2. Create the new bank_accounts table.
        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('type', 16); // cash | bank | external
            $table->string('bank_name', 120)->nullable();
            $table->string('account_name', 120)->nullable();
            $table->string('branch', 120)->nullable();
            $table->string('account_number', 64)->nullable();
            $table->string('currency_code', 3);
            $table->decimal('opening_balance', 18, 4)->default(0);
            $table->decimal('balance', 18, 4)->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_reconciled_at')->nullable();
            $table->decimal('last_reconciled_balance', 18, 4)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['type', 'is_active']);
        });

        // (bank_name, account_number) uniqueness — composite index, MySQL
        // allows multiple NULLs so cash/external rows are unconstrained.
        Schema::table('bank_accounts', function (Blueprint $table): void {
            $table->unique(['bank_name', 'account_number'], 'bank_accounts_number_unique');
        });

        // 3. Create the transfers table.
        Schema::create('transfers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('from_account_id');
            $table->unsignedBigInteger('to_account_id');
            $table->decimal('amount', 18, 4);
            $table->timestamp('occurred_at');
            $table->string('reference', 120)->nullable();
            $table->string('note', 255)->nullable();
            $table->string('transfer_group_id', 36)->unique();
            $table->unsignedBigInteger('performed_by');
            $table->timestamps();

            $table->foreign('from_account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('to_account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('performed_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['from_account_id', 'occurred_at']);
            $table->index(['to_account_id', 'occurred_at']);
        });

        // 4. Rebuild cash_movements against bank_accounts.
        Schema::create('cash_movements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('type', 20);
            $table->decimal('amount', 18, 4);
            $table->unsignedBigInteger('counter_account_id')->nullable();
            $table->decimal('counter_amount', 18, 4)->nullable();
            $table->string('transfer_group_id', 36)->nullable();
            $table->string('reason', 255)->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('performed_by');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('account_id')->references('id')->on('bank_accounts')->cascadeOnDelete();
            $table->foreign('counter_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            $table->foreign('performed_by')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['account_id', 'created_at']);
            $table->index('transfer_group_id');
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Hard cut — no rollback.
    }
};
