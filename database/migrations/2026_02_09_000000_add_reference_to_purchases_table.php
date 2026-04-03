<?php

// database/migrations/2026_02_09_000001_add_reference_to_purchases.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('sequence_no')->nullable()->after('id');
            $table->string('reference', 64)->nullable()->after('sequence_no');

            $table->unique(['company_id', 'sequence_no'], 'uniq_purchase_company_seq');
            $table->unique(['company_id', 'reference'], 'uniq_purchase_company_ref');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique('uniq_purchase_company_seq');
            $table->dropUnique('uniq_purchase_company_ref');
            $table->dropColumn(['reference', 'sequence_no']);
        });
    }
};
