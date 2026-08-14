<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('specialization', 128)->nullable();
            $table->string('license_number', 64)->nullable();
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('years_of_experience')->nullable();
            $table->decimal('consultation_fee', 18, 2)->nullable();
            $table->string('color', 7)->default('#0EA5E9');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
