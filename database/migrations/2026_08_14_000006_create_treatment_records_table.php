<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('visited_at');
            $table->text('chief_complaint')->nullable();
            $table->longText('diagnosis')->nullable();
            $table->longText('treatment_notes')->nullable();
            $table->json('tooth_chart')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->string('status', 16)->default('in_progress');
            $table->timestamps();

            $table->index(['patient_id', 'visited_at']);
            $table->index(['doctor_id', 'visited_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_records');
    }
};
