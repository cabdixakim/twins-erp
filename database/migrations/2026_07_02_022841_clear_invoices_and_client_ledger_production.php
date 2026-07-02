<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clears invoices, invoice_items, and client_ledger_entries —
 * tables missed by the earlier transactional-data wipe.
 * Deletes children before parents to respect FK constraints.
 */
return new class extends Migration
{
    public function up(): void
    {
        // invoice_items is a child of invoices — delete first
        foreach (['invoice_items', 'invoices', 'client_ledger_entries'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    public function down(): void
    {
        // Irreversible data wipe
    }
};
