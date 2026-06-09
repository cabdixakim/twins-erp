<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add paid_by routing to batch_costs
        Schema::table('batch_costs', function (Blueprint $table) {
            $table->string('paid_by_type', 30)->nullable()->after('auto_posted');
            // NULL or 'self' = we paid | 'depot' = depot fronted it | 'transporter' = clearing agent | 'other' = free text
            $table->unsignedBigInteger('paid_by_id')->nullable()->after('paid_by_type');
            // FK to the paying entity (depot_id or transporter_id depending on type)
            $table->string('paid_by_name', 200)->nullable()->after('paid_by_id');
            // Free text name for 'other' payer or display override
        });

        // Add hospitality rate to import nominations
        Schema::table('import_nominations', function (Blueprint $table) {
            $table->decimal('hospitality_rate', 12, 4)->default(0)->after('short_charge_currency');
            // Per-truck hospitality amount at border crossing
            $table->string('hospitality_currency', 8)->default('USD')->after('hospitality_rate');
        });
    }

    public function down(): void
    {
        Schema::table('batch_costs', function (Blueprint $table) {
            $table->dropColumn(['paid_by_type', 'paid_by_id', 'paid_by_name']);
        });
        Schema::table('import_nominations', function (Blueprint $table) {
            $table->dropColumn(['hospitality_rate', 'hospitality_currency']);
        });
    }
};
