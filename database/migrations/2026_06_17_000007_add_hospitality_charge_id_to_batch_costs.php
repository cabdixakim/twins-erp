<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_costs', function (Blueprint $table) {
            $table->unsignedBigInteger('hospitality_charge_id')->nullable()->after('truck_id');
        });
    }

    public function down(): void
    {
        Schema::table('batch_costs', function (Blueprint $table) {
            $table->dropColumn('hospitality_charge_id');
        });
    }
};
