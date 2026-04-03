<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('vessel_name', 255)->nullable()->after('notes');
            $table->string('voyage_no', 100)->nullable()->after('vessel_name');
            $table->string('loading_port', 255)->nullable()->after('voyage_no');
            $table->string('discharge_port', 255)->nullable()->after('loading_port');
            $table->string('bl_number', 100)->nullable()->after('discharge_port');
            $table->date('bl_date')->nullable()->after('bl_number');
            $table->date('eta_date')->nullable()->after('bl_date');
            $table->decimal('qty_delivered', 15, 3)->default(0)->after('eta_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn([
                'vessel_name', 'voyage_no', 'loading_port', 'discharge_port',
                'bl_number', 'bl_date', 'eta_date', 'qty_delivered',
            ]);
        });
    }
};
