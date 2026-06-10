<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transporter_ledger_entries', function (Blueprint $table) {
            // Link advance entries to a specific sale/trip
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete()->after('transporter_id');
            // Sub-type for advance entries: trip | fuel | driver | general | other
            $table->string('advance_type', 20)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('transporter_ledger_entries', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn(['sale_id', 'advance_type']);
        });
    }
};
