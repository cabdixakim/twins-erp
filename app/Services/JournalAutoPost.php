<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

/**
 * Auto-posts double-entry journal entries for all operational transactions.
 * Only fires when accounting_enabled = true AND a Chart of Accounts exists.
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

    public function isEnabled(): bool
    {
        $company = DB::table('companies')->where('id', $this->companyId)->first();
        if (!$company || !$company->accounting_enabled) return false;
        return ChartOfAccount::where('company_id', $this->companyId)->exists();
    }

    // ── 1. Purchase receipt (local depot) ───────────────────────────────────
    // DR Inventory / CR Payables-Suppliers

    public function postPurchaseReceipt(
        int    $purchaseId,
        string $reference,
        float  $amount,
        string $currency,
        string $description
    ): void {
        if (!$this->isEnabled()) return;
        if ($this->alreadyPosted('purchase_receipt', $purchaseId)) return;

        $inventory = $this->account('asset', ['Inventory']);
        $payable   = $this->account('liability', ['Payables – Suppliers', 'Accounts Payable']);
        if (!$inventory || !$payable) return;

        $this->post('purchase', $purchaseId, 'purchase_receipt', $reference, $description, $currency, [
            [$inventory->id, $amount, 0,       $description],
            [$payable->id,   0,       $amount,  $description],
        ]);
    }

    // ── 2. Cross-dock confirm ────────────────────────────────────────────────
    // DR Inventory / CR Payables-Suppliers

    public function postCrossDockConfirm(
        int    $purchaseId,
        string $reference,
        float  $amount,
        string $currency,
        string $description
    ): void {
        if (!$this->isEnabled()) return;
        if ($this->alreadyPosted('purchase_receipt', $purchaseId)) return;

        $inventory = $this->account('asset', ['Inventory']);
        $payable   = $this->account('liability', ['Payables – Suppliers', 'Accounts Payable']);
        if (!$inventory || !$payable) return;

        $this->post('purchase', $purchaseId, 'purchase_receipt', $reference, $description, $currency, [
            [$inventory->id, $amount, 0,      $description],
            [$payable->id,   0,       $amount, $description],
        ]);
    }

    // ── 3. Import truck delivery ─────────────────────────────────────────────
    // DR Inventory / CR Payables-Suppliers

    public function postImportDelivery(
        int    $truckId,
        string $reference,
        float  $amount,
        string $currency,
        string $description
    ): void {
        if (!$this->isEnabled()) return;
        if ($this->alreadyPosted('import_truck', $truckId)) return;

        $inventory = $this->account('asset', ['Inventory']);
        $payable   = $this->account('liability', ['Payables – Suppliers', 'Accounts Payable']);
        if (!$inventory || !$payable) return;

        $this->post('import_truck', $truckId, 'import_truck', $reference, $description, $currency, [
            [$inventory->id, $amount, 0,      $description],
            [$payable->id,   0,       $amount, $description],
        ]);
    }

    // ── 4. Sale post ─────────────────────────────────────────────────────────
    // DR AR / CR Revenue  +  DR COGS / CR Inventory

    public function postSale(
        int    $saleId,
        string $reference,
        float  $revenue,
        float  $cogs,
        string $currency,
        string $description
    ): void {
        if (!$this->isEnabled()) return;
        if ($this->alreadyPosted('sale', $saleId)) return;

        $ar        = $this->account('asset',   ['Accounts Receivable', 'receivable']);
        $revenueAc = $this->account('revenue', ['Revenue', 'Fuel Sales']);
        $cogsAc    = $this->account('expense', ['Cost of Goods Sold', 'Fuel Purchase Cost', 'cogs']);
        $inventory = $this->account('asset',   ['Inventory']);
        if (!$ar || !$revenueAc) return;

        $journal = $this->journal('sale');
        if (!$journal) return;

        DB::transaction(function () use (
            $saleId, $reference, $revenue, $cogs, $currency,
            $description, $ar, $revenueAc, $cogsAc, $inventory, $journal
        ) {
            $e1 = JournalEntry::create([
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
            JournalEntryLine::create(['company_id'=>$this->companyId,'entry_id'=>$e1->id,'account_id'=>$ar->id,       'description'=>'Receivable','debit'=>$revenue,'credit'=>0]);
            JournalEntryLine::create(['company_id'=>$this->companyId,'entry_id'=>$e1->id,'account_id'=>$revenueAc->id,'description'=>'Revenue',   'debit'=>0,       'credit'=>$revenue]);

            if ($cogsAc && $inventory && $cogs > 0) {
                $e2 = JournalEntry::create([
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
                JournalEntryLine::create(['company_id'=>$this->companyId,'entry_id'=>$e2->id,'account_id'=>$cogsAc->id,  'description'=>'COGS',              'debit'=>$cogs,'credit'=>0]);
                JournalEntryLine::create(['company_id'=>$this->companyId,'entry_id'=>$e2->id,'account_id'=>$inventory->id,'description'=>'Inventory reduction','debit'=>0,    'credit'=>$cogs]);
            }
        });
    }

    // ── 5. Supplier payment ──────────────────────────────────────────────────
    // DR Payables-Suppliers / CR Bank (or Petty Cash)

    public function postSupplierPayment(
        int    $ledgerEntryId,
        string $reference,
        float  $amount,
        string $currency,
        string $description,
        string $date
    ): void {
        if (!$this->isEnabled()) return;
        if ($this->alreadyPosted('supplier_payment', $ledgerEntryId)) return;

        $payable = $this->account('liability', ['Payables – Suppliers', 'Accounts Payable']);
        $bank    = $this->account('asset',     ['Main Bank', 'Bank', 'Petty Cash']);
        if (!$payable || !$bank) return;

        $this->post('supplier_payment', $ledgerEntryId, 'supplier_payment', $reference, $description, $currency, [
            [$payable->id, $amount, 0,      $description],
            [$bank->id,    0,       $amount, $description],
        ], $date);
    }

    // ── 6. Transporter payment ───────────────────────────────────────────────
    // DR Payables-Transporters / CR Bank

    public function postTransporterPayment(
        int    $ledgerEntryId,
        string $reference,
        float  $amount,
        string $currency,
        string $description,
        string $date
    ): void {
        if (!$this->isEnabled()) return;
        if ($this->alreadyPosted('transporter_payment', $ledgerEntryId)) return;

        $payable = $this->account('liability', ['Payables – Transporters', 'Accounts Payable']);
        $bank    = $this->account('asset',     ['Main Bank', 'Bank']);
        if (!$payable || !$bank) return;

        $this->post('transporter_payment', $ledgerEntryId, 'transporter_payment', $reference, $description, $currency, [
            [$payable->id, $amount, 0,      $description],
            [$bank->id,    0,       $amount, $description],
        ], $date);
    }

    // ── 7. Depot charge ──────────────────────────────────────────────────────
    // DR Depot Storage & Handling / CR Payables-Depots

    public function postDepotCharge(
        int    $ledgerEntryId,
        string $reference,
        float  $amount,
        string $currency,
        string $description,
        string $date
    ): void {
        if (!$this->isEnabled()) return;
        if ($this->alreadyPosted('depot_charge', $ledgerEntryId)) return;

        $expense = $this->account('expense',   ['Depot Storage', 'Operating Expenses']);
        $payable = $this->account('liability', ['Payables – Depots', 'Accounts Payable']);
        if (!$expense || !$payable) return;

        $this->post('depot_charge', $ledgerEntryId, 'depot_charge', $reference, $description, $currency, [
            [$expense->id, $amount, 0,      $description],
            [$payable->id, 0,       $amount, $description],
        ], $date);
    }

    // ── 8. Depot payment ─────────────────────────────────────────────────────
    // DR Payables-Depots / CR Bank

    public function postDepotPayment(
        int    $ledgerEntryId,
        string $reference,
        float  $amount,
        string $currency,
        string $description,
        string $date
    ): void {
        if (!$this->isEnabled()) return;
        if ($this->alreadyPosted('depot_payment', $ledgerEntryId)) return;

        $payable = $this->account('liability', ['Payables – Depots', 'Accounts Payable']);
        $bank    = $this->account('asset',     ['Main Bank', 'Bank']);
        if (!$payable || !$bank) return;

        $this->post('depot_payment', $ledgerEntryId, 'depot_payment', $reference, $description, $currency, [
            [$payable->id, $amount, 0,      $description],
            [$bank->id,    0,       $amount, $description],
        ], $date);
    }

    // ── 9. Petty cash expense ────────────────────────────────────────────────
    // DR Operating Expenses / CR Petty Cash

    public function postPettyCashExpense(
        int    $txId,
        string $reference,
        float  $amount,
        string $currency,
        string $description,
        string $date
    ): void {
        if (!$this->isEnabled()) return;
        if ($this->alreadyPosted('petty_cash_tx', $txId)) return;

        $expense   = $this->account('expense', ['Operating Expenses', 'Transport']);
        $pettyCash = $this->account('asset',   ['Petty Cash']);
        if (!$expense || !$pettyCash) return;

        $this->post('petty_cash_tx', $txId, 'petty_cash_tx', $reference, $description, $currency, [
            [$expense->id,   $amount, 0,      $description],
            [$pettyCash->id, 0,       $amount, $description],
        ], $date);
    }

    // ── 10. Petty cash top-up ────────────────────────────────────────────────
    // DR Petty Cash / CR Bank

    public function postPettyCashTopUp(
        int    $txId,
        string $reference,
        float  $amount,
        string $currency,
        string $description,
        string $date
    ): void {
        if (!$this->isEnabled()) return;
        if ($this->alreadyPosted('petty_cash_topup', $txId)) return;

        $pettyCash = $this->account('asset', ['Petty Cash']);
        $bank      = $this->account('asset', ['Main Bank', 'Bank']);
        if (!$pettyCash || !$bank) return;

        $this->post('petty_cash_topup', $txId, 'petty_cash_topup', $reference, $description, $currency, [
            [$pettyCash->id, $amount, 0,      $description],
            [$bank->id,      0,       $amount, $description],
        ], $date);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function alreadyPosted(string $refType, int $refId): bool
    {
        return JournalEntry::where('company_id', $this->companyId)
            ->where('ref_type', $refType)
            ->where('ref_id', $refId)
            ->where('status', 'posted')
            ->exists();
    }

    private function post(
        string $refType,
        int    $refId,
        string $uniqueRefType,
        string $reference,
        string $description,
        string $currency,
        array  $lines,
        ?string $date = null
    ): void {
        $journal = $this->journal('general');
        if (!$journal) return;

        DB::transaction(function () use (
            $refType, $refId, $uniqueRefType, $reference,
            $description, $currency, $lines, $date, $journal
        ) {
            $entry = JournalEntry::create([
                'company_id'  => $this->companyId,
                'journal_id'  => $journal->id,
                'reference'   => $reference,
                'description' => $description,
                'entry_date'  => $date ?? now()->toDateString(),
                'status'      => 'posted',
                'ref_type'    => $uniqueRefType,
                'ref_id'      => $refId,
                'posted_by'   => auth()->id(),
                'posted_at'   => now(),
            ]);

            foreach ($lines as [$accountId, $debit, $credit, $desc]) {
                JournalEntryLine::create([
                    'company_id'  => $this->companyId,
                    'entry_id'    => $entry->id,
                    'account_id'  => $accountId,
                    'description' => $desc,
                    'debit'       => $debit,
                    'credit'      => $credit,
                ]);
            }
        });
    }

    private function journal(string $type): ?Journal
    {
        return Journal::where('company_id', $this->companyId)->where('type', $type)->first()
            ?? Journal::where('company_id', $this->companyId)->first();
    }

    private function account(string $type, array $nameHints): ?ChartOfAccount
    {
        $q = ChartOfAccount::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->where('type', 'like', '%' . $type . '%');

        foreach ($nameHints as $hint) {
            $match = (clone $q)->where('name', 'ilike', '%' . $hint . '%')->first();
            if ($match) return $match;
        }

        return $q->first();
    }
}
