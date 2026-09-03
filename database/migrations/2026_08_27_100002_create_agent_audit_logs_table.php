<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('telefono');
            $table->string('canal');
            $table->text('mensaje');
            $table->string('tipo_intencion')->nullable();
            $table->decimal('confianza', 3, 2)->nullable();
            $table->json('tool_calls')->nullable();
            $table->string('fuente')->nullable();
            $table->boolean('requiere_seguimiento_humano')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_audit_logs');
    }
};
