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
        if (! Schema::hasColumn('inventory_adjustments', 'recoverable')) {
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                $table->boolean('recoverable')->default(false)->after('reason_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('inventory_adjustments', 'recoverable')) {
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                $table->dropColumn('recoverable');
            });
        }
    }
};
