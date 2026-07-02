<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clears ALL transactional data while preserving master data.
 *
 * Deleted in FK-safe dependency order (children before parents):
 *   journal_entry_lines → journal_entries
 *   inventory_consumptions → inventory_movements → depot_stocks
 *   batch_costs → import_trucks → import_nominations
 *   sales → purchases → batches → inventory_periods
 *   supplier_ledger_entries, depot_ledger_entries, transporter_ledger_entries
 *   petty_cash_transactions
 *   import_job_rows → import_jobs
 *
 * Preserved (master data):
 *   companies, users, company_user
 *   products, depots, suppliers, transporters, clients
 *   chart_of_accounts, journals
 *   roles, permissions, role_permission, user_roles
 *   petty_cash_accounts
 *
 * Note: Uses DELETE FROM (not TRUNCATE + session_replication_role) so it
 * works on restricted Replit production Postgres where superuser is unavailable.
 */
return new class extends Migration
{
    // Ordered from most-dependent (children) to least-dependent (parents)
    // so FK constraints are never violated.
    private array $tables = [
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

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    public function down(): void
    {
        // Irreversible — data wipe cannot be rolled back
    }
};
