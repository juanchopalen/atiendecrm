<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('wamid')->nullable();
            $table->string('type');
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index('wamid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_events');
    }
};
