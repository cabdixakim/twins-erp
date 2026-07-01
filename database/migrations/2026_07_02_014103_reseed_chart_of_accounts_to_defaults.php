<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Services\SeedCompanyDefaults;

return new class extends Migration
{
    /**
     * Replace the old standardAccounts() CoA (5000/5100=Operating scheme)
     * with the canonical SeedCompanyDefaults scheme (5100=COGS, 5200/5300=Operating).
     *
     * Safe to run because this only touches companies with ZERO journal entry lines.
     */
    public function up(): void
    {
        $companies = DB::table('companies')
            ->where('accounting_enabled', true)
            ->get();

        foreach ($companies as $company) {
            $cid = $company->id;

            // Only proceed if there are no journal entry lines referencing this company's CoA
            $hasEntries = DB::table('journal_entry_lines')
                ->where('company_id', $cid)
                ->exists();

            if ($hasEntries) {
                continue;
            }

            // Delete all CoA accounts and journals for this company
            DB::table('chart_of_accounts')->where('company_id', $cid)->delete();
            DB::table('journals')->where('company_id', $cid)->delete();

            // Re-seed with the correct defaults
            SeedCompanyDefaults::seed($cid);
        }
    }

    public function down(): void
    {
        // Non-reversible data migration
    }
};
