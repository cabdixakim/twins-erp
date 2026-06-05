<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('accounting_enabled')->default(false)->after('base_currency');
            $table->boolean('inventory_periods_enabled')->default(false)->after('accounting_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['accounting_enabled', 'inventory_periods_enabled']);
        });
    }
};
