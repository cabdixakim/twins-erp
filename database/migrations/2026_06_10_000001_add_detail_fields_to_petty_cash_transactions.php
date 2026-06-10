<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            $table->string('recipient', 200)->nullable()->after('description');
            $table->string('reference', 100)->nullable()->after('recipient');
            $table->string('category', 80)->nullable()->after('reference');
            // e.g. fuel, driver_advance, border_fees, hospitality, office, transport, other
        });
    }

    public function down(): void
    {
        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            $table->dropColumn(['recipient', 'reference', 'category']);
        });
    }
};
