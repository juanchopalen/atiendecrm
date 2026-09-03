<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A tenant with no whatsapp_channels of its own uses the shared/common
 * number (this MVP's default, especificacion_multi_tenant_whatsapp.md §9's
 * demo number pattern extended to production tenants). Inboxes for that
 * shared number have no whatsapp_channel_id, so tenant_id is stored
 * directly instead of being derived through the channel relation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inboxes', function (Blueprint $table) {
            $table->foreignId('whatsapp_channel_id')->nullable()->change();
            $table->foreignId('tenant_id')->nullable()->after('whatsapp_channel_id')->constrained()->cascadeOnDelete();
        });

        DB::table('inboxes')
            ->whereNotNull('whatsapp_channel_id')
            ->select('id', 'whatsapp_channel_id')
            ->get()
            ->each(function (object $inbox): void {
                $tenantId = DB::table('whatsapp_channels')->where('id', $inbox->whatsapp_channel_id)->value('tenant_id');

                if ($tenantId) {
                    DB::table('inboxes')->where('id', $inbox->id)->update(['tenant_id' => $tenantId]);
                }
            });

        Schema::table('inboxes', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('inboxes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->foreignId('whatsapp_channel_id')->nullable(false)->change();
        });
    }
};
