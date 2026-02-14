<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // inventory_movements
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_movements', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                $table->index('updated_by');
            }
        });

        // inventory_consumptions
        Schema::table('inventory_consumptions', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_consumptions', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                $table->index('updated_by');
            }
        });
    }

    public function down(): void
    {
        // inventory_movements
        Schema::table('inventory_movements', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_movements', 'updated_by')) {
                $table->dropIndex(['updated_by']);
                $table->dropColumn('updated_by');
            }
        });

        // inventory_consumptions
        Schema::table('inventory_consumptions', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_consumptions', 'updated_by')) {
                $table->dropIndex(['updated_by']);
                $table->dropColumn('updated_by');
            }
        });
    }
};