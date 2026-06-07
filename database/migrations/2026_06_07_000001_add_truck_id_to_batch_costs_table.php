<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_costs', function (Blueprint $table) {
            $table->foreignId('truck_id')
                  ->nullable()
                  ->after('nomination_id')
                  ->constrained('import_trucks')
                  ->nullOnDelete();
            $table->boolean('auto_posted')->default(false)->after('is_included_in_cost');
        });
    }

    public function down(): void
    {
        Schema::table('batch_costs', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\ImportTruck::class);
            $table->dropColumn(['truck_id', 'auto_posted']);
        });
    }
};
