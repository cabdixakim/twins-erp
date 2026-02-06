<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // local_depot purchases need a target depot for receipt
            $table->foreignId('depot_id')
                ->nullable()
                ->after('product_id')
                ->constrained('depots')
                ->nullOnDelete();

            $table->index(['company_id', 'depot_id']);
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'depot_id']);
            $table->dropConstrainedForeignId('depot_id');
        });
    }
};