<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_costs', function (Blueprint $table) {
            // e.g. '2026-06' — set on monthly accrual entries, NULL on at-delivery entries
            $table->string('charge_period', 7)->nullable()->after('depot_charge_config_id');
        });
    }

    public function down(): void
    {
        Schema::table('batch_costs', function (Blueprint $table) {
            $table->dropColumn('charge_period');
        });
    }
};
