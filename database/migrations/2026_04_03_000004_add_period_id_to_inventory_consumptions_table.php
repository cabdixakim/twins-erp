<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_consumptions', function (Blueprint $table) {
            $table->unsignedBigInteger('period_id')->nullable()->after('company_id');
            $table->foreign('period_id')->references('id')->on('inventory_periods')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_consumptions', function (Blueprint $table) {
            $table->dropForeign(['period_id']);
            $table->dropColumn('period_id');
        });
    }
};
