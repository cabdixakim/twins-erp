<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Clears ALL transactional data while preserving master data.
 *
 * Deleted (transactional):
 *   journal_entry_lines, journal_entries
 *   inventory_consumptions, inventory_movements, depot_stocks
 *   batch_costs, import_trucks, import_nominations
 *   sales, purchases, batches
 *   inventory_periods
 *   supplier_ledger_entries, depot_ledger_entries, transporter_ledger_entries
 *   petty_cash_transactions
 *   import_jobs, import_job_rows
 *
 * Preserved (master data):
 *   companies, users, company_user
 *   products, depots, suppliers, transporters, clients
 *   chart_of_accounts, journals
 *   roles, permissions, role_permission, user_roles
 *   petty_cash_accounts
 */
return new class extends Migration
{
    public function up(): void
    {
        // Disable FK checks temporarily so order doesn't matter
        DB::statement('SET session_replication_role = replica;');

        $tables = [
            'journal_entry_lines',
            'journal_entries',
            'inventory_consumptions',
            'inventory_movements',
            'depot_stocks',
            'batch_costs',
            'import_trucks',
            'import_nominations',
            'sales',
            'purchases',
            'batches',
            'inventory_periods',
            'supplier_ledger_entries',
            'depot_ledger_entries',
            'transporter_ledger_entries',
            'petty_cash_transactions',
            'import_job_rows',
            'import_jobs',
        ];

        foreach ($tables as $table) {
            // Only truncate tables that actually exist in this installation
            $exists = DB::selectOne(
                "SELECT to_regclass('public.{$table}') AS t"
            );
            if ($exists && $exists->t !== null) {
                DB::statement("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE");
            }
        }

        // Re-enable FK checks
        DB::statement('SET session_replication_role = DEFAULT;');
    }

    public function down(): void
    {
        // Irreversible — data wipe cannot be rolled back
    }
};
