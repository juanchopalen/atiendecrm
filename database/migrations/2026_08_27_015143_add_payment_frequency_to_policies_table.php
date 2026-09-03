<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->enum('payment_frequency', ['mensual', 'trimestral', 'semestral', 'anual'])
                ->default('anual')
                ->after('premium');
            $table->timestamp('expiration_notified_at')->nullable()->after('payment_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn(['payment_frequency', 'expiration_notified_at']);
        });
    }
};
