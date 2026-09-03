<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('policy_number');
            $table->string('line_of_business');
            $table->string('insurer');
            $table->date('start_date');
            $table->date('expiration_date');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->decimal('premium', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'policy_number']);
            $table->index(['tenant_id', 'expiration_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
