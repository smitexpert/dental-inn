<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            // Drop the placeholder patient_id column from sub-project 1.
            $table->dropColumn('patient_id');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->unsignedBigInteger('patient_id')->nullable()->after('doctor_id');
            $table->foreign('patient_id')
                ->references('id')->on('patients')
                ->nullOnDelete();
            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['patient_id']);
            $table->dropIndex(['patient_id']);
            $table->dropColumn('patient_id');
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->unsignedBigInteger('patient_id')->nullable()->after('doctor_id');
        });
    }
};
