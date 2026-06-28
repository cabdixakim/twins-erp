<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['supplier_ledger_entries', 'client_ledger_entries', 'transporter_ledger_entries'] as $tbl) {
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                if (! Schema::hasColumn($tbl, 'bank_account_id')) {
                    $table->unsignedBigInteger('bank_account_id')->nullable()->after('created_by');
                    $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
                }
                if (! Schema::hasColumn($tbl, 'petty_cash_account_id')) {
                    $table->unsignedBigInteger('petty_cash_account_id')->nullable()->after('bank_account_id');
                    $table->foreign('petty_cash_account_id')->references('id')->on('petty_cash_accounts')->nullOnDelete();
                }
                if (! Schema::hasColumn($tbl, 'bank_transaction_id')) {
                    $table->unsignedBigInteger('bank_transaction_id')->nullable()->after('petty_cash_account_id');
                }
                if (! Schema::hasColumn($tbl, 'petty_cash_transaction_id')) {
                    $table->unsignedBigInteger('petty_cash_transaction_id')->nullable()->after('bank_transaction_id');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['supplier_ledger_entries', 'client_ledger_entries', 'transporter_ledger_entries'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropForeign([$table === 'supplier_ledger_entries' ? 'supplier_ledger_entries_bank_account_id_foreign' : ($table . '_bank_account_id_foreign')]);
                $t->dropColumn(['bank_account_id', 'petty_cash_account_id', 'bank_transaction_id', 'petty_cash_transaction_id']);
            });
        }
    }
};
