<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('import_trucks', 'short_charge_rate_override')) {
            return;
        }
        Schema::table('import_trucks', function (Blueprint $table) {
            // null = use nomination rate; set to override per-truck
            $table->decimal('short_charge_rate_override', 18, 4)->nullable()->after('shortfall_charge');
        });
    }

    public function down(): void
    {
        Schema::table('import_trucks', function (Blueprint $table) {
            $table->dropColumn('short_charge_rate_override');
        });
    }
};
