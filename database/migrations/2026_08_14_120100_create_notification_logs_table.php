<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')
                ->nullable()
                ->constrained('notification_templates')
                ->nullOnDelete();
            $table->enum('channel', ['sms', 'whatsapp', 'email']);
            $table->string('recipient', 160);
            $table->string('subject', 160)->nullable();
            $table->longText('body_rendered');
            $table->enum('status', ['queued', 'sent', 'failed', 'bounced'])->default('queued');
            $table->string('error_message', 1000)->nullable();
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['status', 'created_at']);
            $table->index(['channel', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['template_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
