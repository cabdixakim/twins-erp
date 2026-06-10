<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('ref_id');
            $table->unsignedBigInteger('bank_transaction_id')->nullable()->after('bank_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('petty_cash_transactions', function (Blueprint $table) {
            $table->dropColumn(['bank_account_id', 'bank_transaction_id']);
        });
    }
};
