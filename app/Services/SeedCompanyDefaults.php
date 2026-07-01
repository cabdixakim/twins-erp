<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Seeds the minimum Chart of Accounts + Journals a company needs
 * for JournalAutoPost to function from day one.
 *
 * Safe to call multiple times — uses firstOrCreate logic.
 */
class SeedCompanyDefaults
{
    public static function seed(int $companyId): void
    {
        self::seedJournals($companyId);
        self::seedChartOfAccounts($companyId);
    }

    // ── Journals ─────────────────────────────────────────────────────────────

    private static function seedJournals(int $companyId): void
    {
        $now = now();

        $journals = [
            ['type' => 'general',  'name' => 'General Journal'],
            ['type' => 'purchase', 'name' => 'Purchase Journal'],
            ['type' => 'sale',     'name' => 'Sales Journal'],
            ['type' => 'cash',     'name' => 'Cash & Bank Journal'],
        ];

        foreach ($journals as $j) {
            DB::table('journals')->insertOrIgnore([
                'company_id' => $companyId,
                'name'       => $j['name'],
                'type'       => $j['type'],
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    // ── Chart of Accounts ────────────────────────────────────────────────────

    private static function seedChartOfAccounts(int $companyId): void
    {
        $now = now();

        // Each entry: [code, name, type, sub_type]
        $accounts = [
            // Assets
            ['1100', 'Fuel Inventory',        'asset',     'current'],
            ['1200', 'Accounts Receivable',   'asset',     'current'],
            ['1300', 'Main Bank',              'asset',     'current'],
            ['1310', 'Petty Cash',             'asset',     'current'],

            // Liabilities
            ['2100', 'Payables – Suppliers',   'liability', 'current'],
            ['2200', 'Payables – Transporters','liability', 'current'],
            ['2300', 'Payables – Depots',      'liability', 'current'],

            // Revenue
            ['4100', 'Fuel Sales Revenue',     'revenue',   null],

            // Expenses — COGS parent
            ['5100', 'Cost of Goods Sold',       'expense',   'cogs',      null],
            // COGS sub-accounts (one per batch_cost category + purchase cost)
            ['5101', 'Purchase Cost',             'expense',   'cogs',      '5100'],
            ['5110', 'Freight & Transport',       'expense',   'cogs',      '5100'],
            ['5120', 'Customs & Duty',            'expense',   'cogs',      '5100'],
            ['5130', 'Border Charges',            'expense',   'cogs',      '5100'],
            ['5140', 'Hospitality — COGS',        'expense',   'cogs',      '5100'],
            ['5150', 'Storage — COGS',            'expense',   'cogs',      '5100'],
            ['5160', 'Penalties',                 'expense',   'cogs',      '5100'],
            ['5170', 'Other Landed Costs',        'expense',   'cogs',      '5100'],
            // Operating expenses
            ['5200', 'Depot Storage',             'expense',   'operating', null],
            ['5300', 'Operating Expenses',        'expense',   'operating', null],
        ];

        foreach ($accounts as [$code, $name, $type, $subType, $parentCode]) {
            // Skip if code already exists for this company
            $exists = DB::table('chart_of_accounts')
                ->where('company_id', $companyId)
                ->where('code', $code)
                ->exists();

            if ($exists) continue;

            $parentId = null;
            if ($parentCode) {
                $parentId = DB::table('chart_of_accounts')
                    ->where('company_id', $companyId)
                    ->where('code', $parentCode)
                    ->value('id');
            }

            DB::table('chart_of_accounts')->insert([
                'company_id' => $companyId,
                'code'       => $code,
                'name'       => $name,
                'type'       => $type,
                'sub_type'   => $subType,
                'parent_id'  => $parentId,
                'is_system'  => true,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
