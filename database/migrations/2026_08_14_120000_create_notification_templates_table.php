<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 80);
            $table->enum('channel', ['sms', 'whatsapp', 'email']);
            $table->string('subject', 160)->nullable();
            $table->longText('body');
            $table->boolean('is_active')->default(true);
            $table->string('locale', 8)->default('en');
            $table->timestamps();

            $table->unique(['key', 'channel', 'locale'], 'notification_templates_unique_key_channel_locale');
            $table->index('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
