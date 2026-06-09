<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depot_charge_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('depot_id');

            // What kind of charge this is
            $table->string('category', 30);
            // storage | offloading | duty | customs | other

            $table->string('name', 200);
            // Human label, e.g. "Storage Q1 2026" or "Offloading fee"

            $table->decimal('rate', 14, 6);
            // The numeric rate value

            $table->string('rate_unit', 30);
            // per_m3_per_month | per_m3 | per_trip | lump_sum

            $table->string('currency', 8)->default('USD');

            // Storage billing rules (only applicable when category = storage)
            $table->string('receipt_rule', 40)->nullable();
            // include_receipt_month | exclude_receipt_month | prorate_receipt_month | exclude_first_30_days

            $table->string('dispatch_rule', 40)->nullable();
            // include_dispatch_month | exclude_dispatch_month
            // (used by future monthly storage job)

            // Who pays / who this AP accrues to
            $table->string('paid_by_type', 30)->nullable();
            // self | depot | customs_authority | transporter | other
            $table->unsignedBigInteger('paid_by_id')->nullable();
            $table->string('paid_by_name', 200)->nullable();

            // Rate is effective between these dates (allows rate changes over time)
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('depot_id')->references('id')->on('depots')->cascadeOnDelete();
            $table->index(['company_id', 'depot_id', 'is_active']);
        });

        // Default destination depot for all trucks in a nomination
        Schema::table('import_nominations', function (Blueprint $table) {
            $table->unsignedBigInteger('destination_depot_id')->nullable()->after('purchase_id');
        });

        // Traceability: which config generated this auto-posted cost
        Schema::table('batch_costs', function (Blueprint $table) {
            $table->unsignedBigInteger('depot_charge_config_id')->nullable()->after('truck_id');
        });
    }

    public function down(): void
    {
        Schema::table('batch_costs', function (Blueprint $table) {
            $table->dropColumn('depot_charge_config_id');
        });
        Schema::table('import_nominations', function (Blueprint $table) {
            $table->dropColumn('destination_depot_id');
        });
        Schema::dropIfExists('depot_charge_configs');
    }
};
