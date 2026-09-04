<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Evita re-notificar al agente en cada mensaje si el cliente
            // insiste con preguntas que el agente automático no puede
            // responder; ver App\Services\Agent\AgentOrchestrator.
            $table->timestamp('agent_escalated_at')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('agent_escalated_at');
        });
    }
};
