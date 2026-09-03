<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_webhook_events', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('whatsapp_channel_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_webhook_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};
