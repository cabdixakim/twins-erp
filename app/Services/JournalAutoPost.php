<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

/**
 * Auto-posts double-entry journal entries for purchase receipts and sales.
 * Only fires when the company has accounting_enabled = true AND has a seeded CoA.
 */
class JournalAutoPost
{
    private int $companyId;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
    }

    public static function for(int $companyId): self
    {
        return new self($companyId);
    }

    // ── Guard ────────────────────────────────────────────────────────────────

    public function isEnabled(): bool
    {
        $company = DB::table('companies')->where('id', $this->companyId)->first();
        if (!$company || !$company->accounting_enabled) {
            return false;
        }
        return ChartOfAccount::where('company_id', $this->companyId)->exists();
    }

    // ── Purchase receipt: DR Inventory / CR Accounts Payable ────────────────

    public function postPurchaseReceipt(
        int    $purchaseId,
        string $reference,
        float  $amount,
        string $currency,
        string $description
    ): void {
        if (!$this->isEnabled()) return;

        $alreadyPosted = JournalEntry::where('company_id', $this->companyId)
            ->where('ref_type', 'purchase')
            ->where('ref_id', $purchaseId)
            ->where('status', 'posted')
            ->exists();
        if ($alreadyPosted) return;

        $inventory = $this->findAccount(['asset', 'Inventory', 'inventory']);
        $payable   = $this->findAccount(['liability', 'Accounts Payable', 'payable']);

        if (!$inventory || !$payable) return;

        $journal = Journal::where('company_id', $this->companyId)
            ->where('type', 'purchase')
            ->first()
            ?? Journal::where('company_id', $this->companyId)->first();

        if (!$journal) return;

        DB::transaction(function () use (
            $purchaseId, $reference, $amount, $currency,
            $description, $inventory, $payable, $journal
        ) {
            $entry = JournalEntry::create([
                'company_id'  => $this->companyId,
                'journal_id'  => $journal->id,
                'reference'   => $reference,
                'description' => $description,
                'entry_date'  => now()->toDateString(),
                'status'      => 'posted',
                'ref_type'    => 'purchase',
                'ref_id'      => $purchaseId,
                'posted_by'   => auth()->id(),
                'posted_at'   => now(),
            ]);

            JournalEntryLine::create([
                'company_id'  => $this->companyId,
                'entry_id'    => $entry->id,
                'account_id'  => $inventory->id,
                'description' => $description,
                'debit'       => $amount,
                'credit'      => 0,
            ]);

            JournalEntryLine::create([
                'company_id'  => $this->companyId,
                'entry_id'    => $entry->id,
                'account_id'  => $payable->id,
                'description' => $description,
                'debit'       => 0,
                'credit'      => $amount,
            ]);
        });
    }

    // ── Sale post: DR Accounts Receivable / CR Revenue + DR COGS / CR Inventory ──

    public function postSale(
        int    $saleId,
        string $reference,
        float  $revenue,
        float  $cogs,
        string $currency,
        string $description
    ): void {
        if (!$this->isEnabled()) return;

        $alreadyPosted = JournalEntry::where('company_id', $this->companyId)
            ->where('ref_type', 'sale')
            ->where('ref_id', $saleId)
            ->where('status', 'posted')
            ->exists();
        if ($alreadyPosted) return;

        $ar        = $this->findAccount(['asset', 'Accounts Receivable', 'receivable']);
        $revenueAc = $this->findAccount(['revenue', 'Revenue', 'sales revenue']);
        $cogsAc    = $this->findAccount(['expense', 'Cost of Goods Sold', 'cogs']);
        $inventory = $this->findAccount(['asset', 'Inventory', 'inventory']);

        if (!$ar || !$revenueAc) return;

        $journal = Journal::where('company_id', $this->companyId)
            ->where('type', 'sale')
            ->first()
            ?? Journal::where('company_id', $this->companyId)->first();

        if (!$journal) return;

        DB::transaction(function () use (
            $saleId, $reference, $revenue, $cogs, $currency,
            $description, $ar, $revenueAc, $cogsAc, $inventory, $journal
        ) {
            // Entry 1: Revenue recognition — DR AR / CR Revenue
            $revenueEntry = JournalEntry::create([
                'company_id'  => $this->companyId,
                'journal_id'  => $journal->id,
                'reference'   => $reference,
                'description' => $description . ' — Revenue',
                'entry_date'  => now()->toDateString(),
                'status'      => 'posted',
                'ref_type'    => 'sale',
                'ref_id'      => $saleId,
                'posted_by'   => auth()->id(),
                'posted_at'   => now(),
            ]);

            JournalEntryLine::create([
                'company_id'  => $this->companyId,
                'entry_id'    => $revenueEntry->id,
                'account_id'  => $ar->id,
                'description' => 'Receivable — ' . $description,
                'debit'       => $revenue,
                'credit'      => 0,
            ]);

            JournalEntryLine::create([
                'company_id'  => $this->companyId,
                'entry_id'    => $revenueEntry->id,
                'account_id'  => $revenueAc->id,
                'description' => 'Revenue — ' . $description,
                'debit'       => 0,
                'credit'      => $revenue,
            ]);

            // Entry 2: COGS (if accounts exist and cogs > 0)
            if ($cogsAc && $inventory && $cogs > 0) {
                $cogsEntry = JournalEntry::create([
                    'company_id'  => $this->companyId,
                    'journal_id'  => $journal->id,
                    'reference'   => $reference . '-COGS',
                    'description' => $description . ' — COGS',
                    'entry_date'  => now()->toDateString(),
                    'status'      => 'posted',
                    'ref_type'    => 'sale',
                    'ref_id'      => $saleId,
                    'posted_by'   => auth()->id(),
                    'posted_at'   => now(),
                ]);

                JournalEntryLine::create([
                    'company_id'  => $this->companyId,
                    'entry_id'    => $cogsEntry->id,
                    'account_id'  => $cogsAc->id,
                    'description' => 'COGS — ' . $description,
                    'debit'       => $cogs,
                    'credit'      => 0,
                ]);

                JournalEntryLine::create([
                    'company_id'  => $this->companyId,
                    'entry_id'    => $cogsEntry->id,
                    'account_id'  => $inventory->id,
                    'description' => 'Inventory reduction — ' . $description,
                    'debit'       => 0,
                    'credit'      => $cogs,
                ]);
            }
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Find the best matching account by type + name keywords.
     * $hints = [type_keyword, name_keyword1, name_keyword2]
     */
    private function findAccount(array $hints): ?ChartOfAccount
    {
        $typeHint = $hints[0] ?? null;
        $names    = array_slice($hints, 1);

        $q = ChartOfAccount::where('company_id', $this->companyId)
            ->where('is_active', true);

        if ($typeHint) {
            $q->where('type', 'like', '%' . $typeHint . '%');
        }

        foreach ($names as $name) {
            $match = (clone $q)->where('name', 'ilike', '%' . $name . '%')->first();
            if ($match) return $match;
        }

        return $q->first();
    }
}
