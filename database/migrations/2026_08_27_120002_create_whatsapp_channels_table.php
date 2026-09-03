<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_business_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number_id')->unique();
            $table->string('numero_visible');
            $table->string('departamento')->default('General');
            $table->enum('modo', ['dedicated', 'coexistence'])->default('dedicated');
            $table->enum('estado', ['active', 'pending_verification', 'disabled'])->default('pending_verification');
            $table->enum('calidad', ['green', 'yellow', 'red', 'unknown'])->default('unknown');
            $table->boolean('solo_demo')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'departamento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_channels');
    }
};
