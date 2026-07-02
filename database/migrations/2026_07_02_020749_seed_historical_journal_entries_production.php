<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds 12 historical journal entries for companies that have accounting enabled
 * but zero existing journal entries (i.e. production after initial CoA reseed).
 *
 * Entries covered:
 *   JE1  PO-QT-2026-00001 obligation   Dr Inventory 81,000        / Cr AP-Suppliers 81,000
 *   JE2  PO-QT-2026-00002 obligation   Dr Inventory 1,080,000     / Cr AP-Suppliers 1,080,000
 *   JE3  World oil payment             Dr AP-Suppliers 80,000     / Cr Bank 80,000
 *   JE4  Optima freight 2026-06-15     Dr Freight 78.80           / Cr AP-Transport 78.80
 *   JE5  Optima freight 2026-06-17     Dr Freight 53,966.80       / Cr AP-Transport 53,966.80
 *   JE6  Habib freight  2026-06-17     Dr Freight 5,600           / Cr AP-Transport 5,600
 *   JE7  Habib advance  2026-06-19     Dr AP-Transport 1,300      / Cr Bank 1,300
 *   JE8  Optima freight 2026-06-29     Dr Freight 9,028.25        / Cr AP-Transport 9,028.25
 *   JE9  SO-QT-2026-00001 Revenue      Dr AR 76,000               / Cr Revenue 76,000
 *   JE10 SO-QT-2026-00001 COGS         Dr PurchaseCost 54,000     / Cr Inventory 54,000
 *   JE11 SO-QT-2026-00002 Revenue      Dr AR 36,000               / Cr Revenue 36,000
 *   JE12 SO-QT-2026-00002 COGS         Dr PurchaseCost 27,000     / Cr Inventory 27,000
 *
 * Trial balance (verified): Grand Total Dr = Grand Total Cr = $1,503,973.85
 */
return new class extends Migration
{
    public function up(): void
    {
        $companies = DB::table('companies')
            ->where('accounting_enabled', true)
            ->get();

        foreach ($companies as $company) {
            $cid = $company->id;

            // Only run for companies that have no journal entries yet
            if (DB::table('journal_entries')->where('company_id', $cid)->exists()) {
                continue;
            }

            // ── Resolve IDs dynamically ──────────────────────────────────────
            $acct = fn(string $code) => DB::table('chart_of_accounts')
                ->where('company_id', $cid)
                ->where('code', $code)
                ->where('is_active', true)
                ->value('id');

            $jrnl = fn(string $type) => DB::table('journals')
                ->where('company_id', $cid)
                ->where('type', $type)
                ->value('id');

            $period = DB::table('inventory_periods')
                ->where('company_id', $cid)
                ->where('status', 'open')
                ->value('id');

            $postedBy = DB::table('users')
                ->join('company_user', 'company_user.user_id', '=', 'users.id')
                ->where('company_user.company_id', $cid)
                ->orderBy('users.id')
                ->value('users.id');

            $genJournal  = $jrnl('general');
            $saleJournal = $jrnl('sale');

            $inv   = $acct('1100'); // Fuel Inventory
            $ar    = $acct('1200'); // Accounts Receivable
            $bank  = $acct('1300'); // Main Bank
            $apSup = $acct('2100'); // Payables – Suppliers
            $apTrn = $acct('2200'); // Payables – Transporters
            $rev   = $acct('4100'); // Fuel Sales Revenue
            $pc    = $acct('5101'); // Purchase Cost (COGS sub-account)
            $frt   = $acct('5110'); // Freight & Transport

            // Abort silently if any critical account or journal is missing
            if (!$inv || !$apSup || !$ar || !$rev || !$pc || !$genJournal || !$saleJournal) {
                continue;
            }

            $now = now();

            DB::transaction(function () use (
                $cid, $genJournal, $saleJournal, $period, $postedBy, $now,
                $inv, $ar, $bank, $apSup, $apTrn, $rev, $pc, $frt
            ) {
                // Helper: insert one journal entry + its lines atomically
                $je = function (
                    int     $journalId,
                    string  $ref,
                    string  $desc,
                    string  $date,
                    ?string $refType,
                    ?int    $refId,
                    array   $lines
                ) use ($cid, $period, $postedBy, $now): void {
                    $entryId = DB::table('journal_entries')->insertGetId([
                        'company_id'  => $cid,
                        'journal_id'  => $journalId,
                        'period_id'   => $period,
                        'reference'   => $ref,
                        'description' => $desc,
                        'entry_date'  => $date,
                        'status'      => 'posted',
                        'ref_type'    => $refType,
                        'ref_id'      => $refId,
                        'posted_by'   => $postedBy,
                        'posted_at'   => $now,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);

                    foreach ($lines as [$accountId, $debit, $credit, $lineDesc]) {
                        DB::table('journal_entry_lines')->insert([
                            'company_id'  => $cid,
                            'entry_id'    => $entryId,
                            'account_id'  => $accountId,
                            'description' => $lineDesc,
                            'debit'       => $debit,
                            'credit'      => $credit,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ]);
                    }
                };

                // ── JE1: Purchase obligation — PO-QT-2026-00001 ─────────────
                // World oil · 9,000 L × $9.00 = $81,000
                $je($genJournal,
                    'PO-QT-2026-00001',
                    'Import purchase PO-QT-2026-00001 — obligation confirmed',
                    '2026-06-14', 'purchase_receipt', 1,
                    [
                        [$inv,   81000.00, 0,        'Import purchase PO-QT-2026-00001'],
                        [$apSup, 0,        81000.00, 'Import purchase PO-QT-2026-00001'],
                    ]
                );

                // ── JE2: Purchase obligation — PO-QT-2026-00002 ─────────────
                // PETRO SEVEN · 800,000 L × $1.35 = $1,080,000
                $je($genJournal,
                    'PO-QT-2026-00002',
                    'Import purchase PO-QT-2026-00002 — obligation confirmed',
                    '2026-06-17', 'purchase_receipt', 2,
                    [
                        [$inv,   1080000.00, 0,          'Import purchase PO-QT-2026-00002'],
                        [$apSup, 0,          1080000.00, 'Import purchase PO-QT-2026-00002'],
                    ]
                );

                // ── JE3: Supplier payment — World oil $80,000 ────────────────
                // supplier_ledger_entries.id = 2  (payment entry)
                if ($bank) {
                    $je($genJournal,
                        'PMT-WORLDOIL-20260614',
                        'Supplier payment — World oil',
                        '2026-06-14', 'supplier_payment', 2,
                        [
                            [$apSup, 80000.00, 0,        'Supplier payment — World oil'],
                            [$bank,  0,        80000.00, 'Supplier payment — World oil'],
                        ]
                    );
                }

                if ($frt && $apTrn) {
                    // ── JE4: Freight accrual — Optima, 2026-06-15 (Batch 1) ──
                    // freight $79.00 − short charge $0.20 = $78.80
                    $je($genJournal,
                        'FRT-OPTIMA-20260615',
                        'Freight accrual — Optima (PO-QT-2026-00001)',
                        '2026-06-15', null, null,
                        [
                            [$frt,   78.80, 0,     'Freight accrual — Optima (PO-QT-2026-00001)'],
                            [$apTrn, 0,     78.80, 'Freight accrual — Optima (PO-QT-2026-00001)'],
                        ]
                    );

                    // ── JE5: Freight accrual — Optima, 2026-06-17 (Batch 2) ──
                    // 17,922 + 9,240 + 9,215.80 + 9,240 + 9,218 − 869 = $53,966.80
                    $je($genJournal,
                        'FRT-OPTIMA-20260617',
                        'Freight accrual — Optima (PO-QT-2026-00002)',
                        '2026-06-17', null, null,
                        [
                            [$frt,   53966.80, 0,        'Freight accrual — Optima (PO-QT-2026-00002)'],
                            [$apTrn, 0,        53966.80, 'Freight accrual — Optima (PO-QT-2026-00002)'],
                        ]
                    );

                    // ── JE6: Freight accrual — Habib Trucks, 2026-06-17 ──────
                    // $2,800 + $2,800 = $5,600
                    $je($genJournal,
                        'FRT-HABIB-20260617',
                        'Freight accrual — Habib Trucks (PO-QT-2026-00002)',
                        '2026-06-17', null, null,
                        [
                            [$frt,   5600.00, 0,       'Freight accrual — Habib Trucks (PO-QT-2026-00002)'],
                            [$apTrn, 0,       5600.00, 'Freight accrual — Habib Trucks (PO-QT-2026-00002)'],
                        ]
                    );

                    // ── JE7: Transporter advance — Habib Trucks, 2026-06-19 ──
                    // $1,300 prepayment reduces AP-Transporters balance
                    if ($bank) {
                        $je($genJournal,
                            'ADV-HABIB-20260619',
                            'Advance payment — Habib Trucks',
                            '2026-06-19', null, null,
                            [
                                [$apTrn, 1300.00, 0,       'Advance payment — Habib Trucks'],
                                [$bank,  0,       1300.00, 'Advance payment — Habib Trucks'],
                            ]
                        );
                    }

                    // ── JE8: Freight accrual — Optima, 2026-06-29 (Batch 2) ──
                    // $9,130 − $101.75 short charge = $9,028.25
                    $je($genJournal,
                        'FRT-OPTIMA-20260629',
                        'Freight accrual — Optima (PO-QT-2026-00002)',
                        '2026-06-29', null, null,
                        [
                            [$frt,   9028.25, 0,       'Freight accrual — Optima (PO-QT-2026-00002)'],
                            [$apTrn, 0,       9028.25, 'Freight accrual — Optima (PO-QT-2026-00002)'],
                        ]
                    );
                }

                // ── JE9: Sale SO-QT-2026-00001 — Revenue ─────────────────────
                // 40,000 L × $1.90 = $76,000
                $je($saleJournal,
                    'SO-QT-2026-00001',
                    'Sale SO-QT-2026-00001 — Revenue',
                    '2026-06-17', 'sale', 1,
                    [
                        [$ar,  76000.00, 0,        'Receivable'],
                        [$rev, 0,        76000.00, 'Revenue'],
                    ]
                );

                // ── JE10: Sale SO-QT-2026-00001 — COGS ───────────────────────
                // 40,000 L × $1.35 unit cost = $54,000
                $je($saleJournal,
                    'SO-QT-2026-00001-COGS',
                    'Sale SO-QT-2026-00001 — COGS',
                    '2026-06-17', 'sale', 1,
                    [
                        [$pc,  54000.00, 0,        'Purchase Cost'],
                        [$inv, 0,        54000.00, 'Inventory reduction'],
                    ]
                );

                // ── JE11: Sale SO-QT-2026-00002 — Revenue ────────────────────
                // 20,000 L × $1.80 = $36,000
                $je($saleJournal,
                    'SO-QT-2026-00002',
                    'Sale SO-QT-2026-00002 — Revenue',
                    '2026-06-17', 'sale', 2,
                    [
                        [$ar,  36000.00, 0,        'Receivable'],
                        [$rev, 0,        36000.00, 'Revenue'],
                    ]
                );

                // ── JE12: Sale SO-QT-2026-00002 — COGS ───────────────────────
                // 20,000 L × $1.35 unit cost = $27,000
                $je($saleJournal,
                    'SO-QT-2026-00002-COGS',
                    'Sale SO-QT-2026-00002 — COGS',
                    '2026-06-17', 'sale', 2,
                    [
                        [$pc,  27000.00, 0,        'Purchase Cost'],
                        [$inv, 0,        27000.00, 'Inventory reduction'],
                    ]
                );
            });
        }
    }

    public function down(): void
    {
        // Non-reversible data seeding migration
    }
};
