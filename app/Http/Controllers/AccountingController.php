<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    private function cid(): int
    {
        return (int) auth()->user()->active_company_id;
    }

    /* ------------------------------------------------------------------ */
    /*  Hub                                                                  */
    /* ------------------------------------------------------------------ */

    public function index()
    {
        $cid = $this->cid();

        $coaCount      = ChartOfAccount::where('company_id', $cid)->count();
        $journalCount  = JournalEntry::where('company_id', $cid)->where('status', 'posted')->count();
        $draftCount    = JournalEntry::where('company_id', $cid)->where('status', 'draft')->count();

        // Quick P&L summary (current month) from operational data
        $from = now()->startOfMonth()->toDateString();
        $to   = now()->toDateString();

        $revenue = DB::table('sales')
            ->where('company_id', $cid)
            ->where('status', 'posted')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->sum('total');

        $cogsTotal = DB::table('inventory_consumptions')
            ->where('company_id', $cid)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->sum(DB::raw('qty * unit_cost'));

        $landedCosts = DB::table('batch_costs')
            ->join('batches', 'batches.id', '=', 'batch_costs.batch_id')
            ->where('batches.company_id', $cid)
            ->whereBetween(DB::raw('DATE(batch_costs.created_at)'), [$from, $to])
            ->sum('batch_costs.amount');

        $summary = [
            'coa_count'     => $coaCount,
            'journal_count' => $journalCount,
            'draft_count'   => $draftCount,
            'revenue_mtd'   => round((float)$revenue, 2),
            'cogs_mtd'      => round((float)$cogsTotal + (float)$landedCosts, 2),
            'gross_profit'  => round((float)$revenue - (float)$cogsTotal - (float)$landedCosts, 2),
        ];

        return view('accounting.index', compact('summary'));
    }

    /* ------------------------------------------------------------------ */
    /*  Chart of Accounts                                                    */
    /* ------------------------------------------------------------------ */

    public function chartOfAccounts(Request $request)
    {
        $cid    = $this->cid();
        $type   = $request->input('type');
        $search = $request->input('search');

        $q = ChartOfAccount::where('company_id', $cid)
            ->with('parent')
            ->orderBy('code');

        if ($type)   $q->where('type', $type);
        if ($search) $q->where(function ($q) use ($search) {
            $q->where('name', 'ilike', "%{$search}%")
              ->orWhere('code', 'ilike', "%{$search}%");
        });

        $accounts  = $q->get();
        $hasAccounts = ChartOfAccount::where('company_id', $cid)->exists();

        return view('accounting.chart-of-accounts', compact('accounts', 'type', 'search', 'hasAccounts'));
    }

    public function storeAccount(Request $request)
    {
        $cid = $this->cid();
        $data = $request->validate([
            'code'     => 'required|string|max:32',
            'name'     => 'required|string|max:200',
            'type'     => 'required|in:asset,liability,equity,revenue,expense',
            'sub_type' => 'nullable|string|max:40',
            'parent_id'=> 'nullable|exists:chart_of_accounts,id',
        ]);

        $exists = ChartOfAccount::where('company_id', $cid)->where('code', $data['code'])->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'Account code already exists.'])->withInput();
        }

        ChartOfAccount::create(array_merge($data, ['company_id' => $cid]));

        return back()->with('success', 'Account created.');
    }

    public function updateAccount(Request $request, ChartOfAccount $account)
    {
        abort_unless($account->company_id === $this->cid(), 403);

        $data = $request->validate([
            'name'      => 'required|string|max:200',
            'sub_type'  => 'nullable|string|max:40',
            'is_active' => 'boolean',
        ]);

        $account->update($data);

        return back()->with('success', 'Account updated.');
    }

    public function destroyAccount(ChartOfAccount $account)
    {
        abort_unless($account->company_id === $this->cid(), 403);

        if ($account->is_system) {
            return back()->withErrors(['delete' => 'System accounts cannot be deleted.']);
        }

        $hasLines = JournalEntryLine::where('account_id', $account->id)->exists();
        if ($hasLines) {
            return back()->withErrors(['delete' => 'Account has journal entries and cannot be deleted.']);
        }

        $account->delete();

        return back()->with('success', 'Account deleted.');
    }

    public function seedAccounts(Request $request)
    {
        $cid = $this->cid();

        if (ChartOfAccount::where('company_id', $cid)->exists()) {
            return back()->with('info', 'Chart of accounts already has entries.');
        }

        $standard = $this->standardAccounts();

        DB::transaction(function () use ($cid, $standard) {
            $parentMap = [];
            foreach ($standard as $acct) {
                $parentId = isset($acct['parent_code']) ? ($parentMap[$acct['parent_code']] ?? null) : null;
                $created  = ChartOfAccount::create([
                    'company_id' => $cid,
                    'code'       => $acct['code'],
                    'name'       => $acct['name'],
                    'type'       => $acct['type'],
                    'sub_type'   => $acct['sub_type'] ?? null,
                    'parent_id'  => $parentId,
                    'is_system'  => true,
                    'is_active'  => true,
                ]);
                $parentMap[$acct['code']] = $created->id;
            }

            // Seed default journals
            $journalTypes = [
                ['name' => 'General Journal',   'type' => 'general'],
                ['name' => 'Purchase Journal',  'type' => 'purchase'],
                ['name' => 'Sales Journal',     'type' => 'sale'],
                ['name' => 'Cash Journal',      'type' => 'cash'],
                ['name' => 'Bank Journal',      'type' => 'bank'],
            ];
            foreach ($journalTypes as $j) {
                Journal::firstOrCreate(
                    ['company_id' => $cid, 'type' => $j['type']],
                    ['name' => $j['name'], 'is_active' => true]
                );
            }
        });

        return back()->with('success', 'Standard chart of accounts seeded successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  P&L — derived from operational data                                 */
    /* ------------------------------------------------------------------ */

    public function pl(Request $request)
    {
        $cid  = $this->cid();
        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to   = $request->input('to', now()->toDateString());

        // Revenue from posted sales
        $revenueRows = DB::table('sales')
            ->join('products', 'products.id', '=', 'sales.product_id')
            ->where('sales.company_id', $cid)
            ->where('sales.status', 'posted')
            ->whereBetween(DB::raw('DATE(sales.created_at)'), [$from, $to])
            ->selectRaw('products.name as product_name, SUM(sales.total) as revenue, SUM(sales.qty) as qty')
            ->groupBy('products.name')
            ->orderByDesc('revenue')
            ->get();

        $totalRevenue = $revenueRows->sum('revenue');

        // COGS from inventory consumptions
        $cogsRows = DB::table('inventory_consumptions')
            ->join('products', 'products.id', '=', 'inventory_consumptions.product_id')
            ->where('inventory_consumptions.company_id', $cid)
            ->whereBetween(DB::raw('DATE(inventory_consumptions.created_at)'), [$from, $to])
            ->selectRaw('products.name as product_name, SUM(inventory_consumptions.qty * inventory_consumptions.unit_cost) as cogs, SUM(inventory_consumptions.qty) as qty')
            ->groupBy('products.name')
            ->orderByDesc('cogs')
            ->get();

        $totalCogs = $cogsRows->sum('cogs');

        // Landed costs (batch_costs — freight, duty, etc.)
        $landedCosts = DB::table('batch_costs')
            ->join('batches', 'batches.id', '=', 'batch_costs.batch_id')
            ->where('batches.company_id', $cid)
            ->whereBetween(DB::raw('DATE(batch_costs.created_at)'), [$from, $to])
            ->selectRaw("batch_costs.category, SUM(batch_costs.amount) as total")
            ->groupBy('batch_costs.category')
            ->orderByDesc('total')
            ->get();

        $totalLanded = $landedCosts->sum('total');

        // Operating expenses from petty cash (expense type only)
        $pettyCashExpenses = DB::table('petty_cash_transactions')
            ->join('petty_cash_accounts', 'petty_cash_accounts.id', '=', 'petty_cash_transactions.account_id')
            ->where('petty_cash_accounts.company_id', $cid)
            ->where('petty_cash_transactions.type', 'expense')
            ->whereBetween(DB::raw('DATE(petty_cash_transactions.created_at)'), [$from, $to])
            ->selectRaw("petty_cash_transactions.category, SUM(petty_cash_transactions.amount) as total")
            ->groupBy('petty_cash_transactions.category')
            ->orderByDesc('total')
            ->get();

        $totalPettyCash = $pettyCashExpenses->sum('total');

        // Transporter freight charges from ledger
        $transporterCharges = DB::table('transporter_ledger_entries')
            ->join('transporters', 'transporters.id', '=', 'transporter_ledger_entries.transporter_id')
            ->where('transporters.company_id', $cid)
            ->where('transporter_ledger_entries.type', 'freight_charge')
            ->whereBetween(DB::raw('DATE(transporter_ledger_entries.created_at)'), [$from, $to])
            ->sum('transporter_ledger_entries.amount');

        // Depot charges from ledger
        $depotCharges = DB::table('depot_ledger_entries')
            ->join('depots', 'depots.id', '=', 'depot_ledger_entries.depot_id')
            ->where('depots.company_id', $cid)
            ->whereIn('depot_ledger_entries.type', ['storage_charge','handling_fee','loading_fee','other_charge'])
            ->whereBetween(DB::raw('DATE(depot_ledger_entries.created_at)'), [$from, $to])
            ->sum('depot_ledger_entries.amount');

        $grossProfit  = $totalRevenue - $totalCogs - $totalLanded;
        $totalOpex    = $totalPettyCash + $transporterCharges + $depotCharges;
        $netProfit    = $grossProfit - $totalOpex;
        $grossMargin  = $totalRevenue > 0 ? round($grossProfit / $totalRevenue * 100, 1) : null;
        $netMargin    = $totalRevenue > 0 ? round($netProfit / $totalRevenue * 100, 1) : null;

        return view('accounting.pl', compact(
            'revenueRows', 'cogsRows', 'landedCosts', 'pettyCashExpenses',
            'totalRevenue', 'totalCogs', 'totalLanded', 'totalPettyCash',
            'transporterCharges', 'depotCharges',
            'grossProfit', 'totalOpex', 'netProfit',
            'grossMargin', 'netMargin',
            'from', 'to'
        ));
    }

    /* ------------------------------------------------------------------ */
    /*  Balance Sheet — snapshot of assets vs liabilities                   */
    /* ------------------------------------------------------------------ */

    public function balanceSheet(Request $request)
    {
        $cid = $this->cid();
        $asOf = $request->input('as_of', today()->toDateString());

        // ── ASSETS ──────────────────────────────────────────────────────

        // Bank balances (opening + non-voided transactions up to asOf)
        $bankAccounts = DB::table('bank_accounts')
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->get();

        $bankTotal = 0;
        $bankRows  = [];
        foreach ($bankAccounts as $bank) {
            $movements = DB::table('bank_transactions')
                ->where('bank_account_id', $bank->id)
                ->whereNull('voided_at')
                ->whereDate('entry_date', '<=', $asOf)
                ->selectRaw("SUM(CASE WHEN type IN ('deposit','transfer_in') THEN amount ELSE -amount END) as net")
                ->value('net');
            $balance = (float)$bank->opening_balance + (float)($movements ?? 0);
            $bankRows[] = (object)['name' => $bank->name, 'currency' => $bank->currency, 'balance' => $balance];
            $bankTotal += $balance;
        }

        // Petty cash floats (computed from transactions since no current_balance column)
        $pettyCashTotal = (float) DB::select("
            SELECT COALESCE(SUM(CASE WHEN pct.type = 'top_up' THEN pct.amount ELSE -pct.amount END), 0) as bal
            FROM petty_cash_transactions pct
            JOIN petty_cash_accounts pca ON pca.id = pct.account_id
            WHERE pca.company_id = ? AND pca.is_active = true
        ", [$cid])[0]->bal ?? 0;

        // Inventory (stock value = qty_on_hand × unit_cost)
        $inventoryValue = DB::table('depot_stocks')
            ->join('depots', 'depots.id', '=', 'depot_stocks.depot_id')
            ->where('depot_stocks.company_id', $cid)
            ->where('depots.is_system', false)
            ->where('depots.is_active', true)
            ->sum(DB::raw('depot_stocks.qty_on_hand * depot_stocks.unit_cost'));

        // Accounts Receivable (open client invoices)
        $arTotal = DB::table('invoices')
            ->where('company_id', $cid)
            ->whereNotIn('status', ['void','paid'])
            ->whereDate('created_at', '<=', $asOf)
            ->sum(DB::raw('total - COALESCE(paid_amount,0)'));

        $totalAssets = $bankTotal + (float)$pettyCashTotal + (float)$inventoryValue + (float)$arTotal;

        // ── LIABILITIES ─────────────────────────────────────────────────

        // Supplier payables
        $supplierPayables = DB::table('supplier_ledger_entries')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_ledger_entries.supplier_id')
            ->where('suppliers.company_id', $cid)
            ->whereDate('supplier_ledger_entries.entry_date', '<=', $asOf)
            ->sum('supplier_ledger_entries.amount');  // positive = owed to supplier

        // Transporter payables (charges - payments)
        $transporterPayables = DB::table('transporter_ledger_entries')
            ->join('transporters', 'transporters.id', '=', 'transporter_ledger_entries.transporter_id')
            ->where('transporters.company_id', $cid)
            ->whereDate('transporter_ledger_entries.entry_date', '<=', $asOf)
            ->sum(DB::raw("CASE WHEN transporter_ledger_entries.type IN ('freight_charge','advance') THEN transporter_ledger_entries.amount ELSE -transporter_ledger_entries.amount END"));

        // Depot payables
        $depotPayables = DB::table('depot_ledger_entries')
            ->join('depots', 'depots.id', '=', 'depot_ledger_entries.depot_id')
            ->where('depots.company_id', $cid)
            ->whereDate('depot_ledger_entries.entry_date', '<=', $asOf)
            ->sum(DB::raw("CASE WHEN depot_ledger_entries.type IN ('storage_charge','handling_fee','loading_fee','other_charge') THEN depot_ledger_entries.amount ELSE -depot_ledger_entries.amount END"));

        $totalLiabilities = max(0, (float)$supplierPayables)
                          + max(0, (float)$transporterPayables)
                          + max(0, (float)$depotPayables);

        $equity = $totalAssets - $totalLiabilities;

        return view('accounting.balance-sheet', compact(
            'bankRows', 'bankTotal', 'pettyCashTotal', 'inventoryValue', 'arTotal', 'totalAssets',
            'supplierPayables', 'transporterPayables', 'depotPayables', 'totalLiabilities',
            'equity', 'asOf'
        ));
    }

    /* ------------------------------------------------------------------ */
    /*  Journals                                                             */
    /* ------------------------------------------------------------------ */

    public function journals(Request $request)
    {
        $cid    = $this->cid();
        $status = $request->input('status', '');
        $from   = $request->input('from');
        $to     = $request->input('to');

        $q = JournalEntry::where('company_id', $cid)
            ->with(['journal', 'lines.account', 'postedBy'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id');

        if ($status) $q->where('status', $status);
        if ($from)   $q->whereDate('entry_date', '>=', $from);
        if ($to)     $q->whereDate('entry_date', '<=', $to);

        $entries  = $q->paginate(30)->withQueryString();
        $journals = Journal::where('company_id', $cid)->get();
        $accounts = ChartOfAccount::where('company_id', $cid)->where('is_active', true)->orderBy('code')->get();

        return view('accounting.journals', compact('entries', 'journals', 'accounts', 'status', 'from', 'to'));
    }

    public function storeJournal(Request $request)
    {
        $cid = $this->cid();

        $data = $request->validate([
            'journal_id'  => 'required|exists:journals,id',
            'reference'   => 'required|string|max:80',
            'description' => 'required|string|max:500',
            'entry_date'  => 'required|date',
            'lines'       => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.description'=> 'nullable|string|max:500',
            'lines.*.debit'      => 'required|numeric|min:0',
            'lines.*.credit'     => 'required|numeric|min:0',
        ]);

        $totalDebit  = collect($data['lines'])->sum('debit');
        $totalCredit = collect($data['lines'])->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withErrors(['lines' => 'Debits must equal credits (difference: ' . number_format($totalDebit - $totalCredit, 2) . ').'])->withInput();
        }

        DB::transaction(function () use ($cid, $data) {
            $entry = JournalEntry::create([
                'company_id'  => $cid,
                'journal_id'  => $data['journal_id'],
                'reference'   => $data['reference'],
                'description' => $data['description'],
                'entry_date'  => $data['entry_date'],
                'status'      => 'posted',
                'posted_by'   => auth()->id(),
                'posted_at'   => now(),
            ]);

            foreach ($data['lines'] as $line) {
                JournalEntryLine::create([
                    'company_id'  => $cid,
                    'entry_id'    => $entry->id,
                    'account_id'  => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit'       => (float)$line['debit'],
                    'credit'      => (float)$line['credit'],
                ]);
            }
        });

        return back()->with('success', 'Journal entry posted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  Trial Balance                                                        */
    /* ------------------------------------------------------------------ */

    public function trialBalance(Request $request)
    {
        $cid  = $this->cid();
        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to   = $request->input('to', today()->toDateString());

        $lines = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entry_lines.company_id', $cid)
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.entry_date', [$from, $to])
            ->selectRaw('
                chart_of_accounts.code,
                chart_of_accounts.name,
                chart_of_accounts.type,
                SUM(journal_entry_lines.debit) as total_debit,
                SUM(journal_entry_lines.credit) as total_credit
            ')
            ->groupBy('chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.type')
            ->orderBy('chart_of_accounts.code')
            ->get();

        $totals = [
            'debit'  => $lines->sum('total_debit'),
            'credit' => $lines->sum('total_credit'),
        ];

        $balanced = abs($totals['debit'] - $totals['credit']) < 0.01;

        return view('accounting.trial-balance', compact('lines', 'totals', 'balanced', 'from', 'to'));
    }

    /* ------------------------------------------------------------------ */
    /*  Standard chart of accounts template                                 */
    /* ------------------------------------------------------------------ */

    private function standardAccounts(): array
    {
        return [
            // ── ASSETS ────────────────────────────────────────────────
            ['code'=>'1000','name'=>'Assets',                    'type'=>'asset',    'sub_type'=>null],
            ['code'=>'1100','name'=>'Cash & Bank',               'type'=>'asset',    'sub_type'=>'current_asset',  'parent_code'=>'1000'],
            ['code'=>'1101','name'=>'Main Bank Account',         'type'=>'asset',    'sub_type'=>'current_asset',  'parent_code'=>'1100'],
            ['code'=>'1102','name'=>'Petty Cash',                'type'=>'asset',    'sub_type'=>'current_asset',  'parent_code'=>'1100'],
            ['code'=>'1200','name'=>'Accounts Receivable',       'type'=>'asset',    'sub_type'=>'current_asset',  'parent_code'=>'1000'],
            ['code'=>'1201','name'=>'Trade Receivables – Clients','type'=>'asset',   'sub_type'=>'current_asset',  'parent_code'=>'1200'],
            ['code'=>'1300','name'=>'Inventory',                 'type'=>'asset',    'sub_type'=>'current_asset',  'parent_code'=>'1000'],
            ['code'=>'1301','name'=>'Fuel Stock – AGO',          'type'=>'asset',    'sub_type'=>'current_asset',  'parent_code'=>'1300'],
            ['code'=>'1302','name'=>'Fuel Stock – PMS',          'type'=>'asset',    'sub_type'=>'current_asset',  'parent_code'=>'1300'],
            ['code'=>'1400','name'=>'Prepayments & Advances',    'type'=>'asset',    'sub_type'=>'current_asset',  'parent_code'=>'1000'],
            ['code'=>'1500','name'=>'Fixed Assets',              'type'=>'asset',    'sub_type'=>'fixed_asset',    'parent_code'=>'1000'],
            ['code'=>'1501','name'=>'Vehicles & Equipment',      'type'=>'asset',    'sub_type'=>'fixed_asset',    'parent_code'=>'1500'],

            // ── LIABILITIES ───────────────────────────────────────────
            ['code'=>'2000','name'=>'Liabilities',               'type'=>'liability','sub_type'=>null],
            ['code'=>'2100','name'=>'Accounts Payable',          'type'=>'liability','sub_type'=>'current_liability','parent_code'=>'2000'],
            ['code'=>'2101','name'=>'Payables – Suppliers',      'type'=>'liability','sub_type'=>'current_liability','parent_code'=>'2100'],
            ['code'=>'2102','name'=>'Payables – Transporters',   'type'=>'liability','sub_type'=>'current_liability','parent_code'=>'2100'],
            ['code'=>'2103','name'=>'Payables – Depots',         'type'=>'liability','sub_type'=>'current_liability','parent_code'=>'2100'],
            ['code'=>'2200','name'=>'Short-term Loans',          'type'=>'liability','sub_type'=>'current_liability','parent_code'=>'2000'],
            ['code'=>'2300','name'=>'Tax Liabilities',           'type'=>'liability','sub_type'=>'current_liability','parent_code'=>'2000'],

            // ── EQUITY ────────────────────────────────────────────────
            ['code'=>'3000','name'=>'Equity',                    'type'=>'equity',   'sub_type'=>null],
            ['code'=>'3001','name'=>"Owner's Capital",           'type'=>'equity',   'sub_type'=>'equity',         'parent_code'=>'3000'],
            ['code'=>'3002','name'=>'Retained Earnings',         'type'=>'equity',   'sub_type'=>'equity',         'parent_code'=>'3000'],

            // ── REVENUE ───────────────────────────────────────────────
            ['code'=>'4000','name'=>'Revenue',                   'type'=>'revenue',  'sub_type'=>null],
            ['code'=>'4001','name'=>'Fuel Sales – AGO',          'type'=>'revenue',  'sub_type'=>'operating',      'parent_code'=>'4000'],
            ['code'=>'4002','name'=>'Fuel Sales – PMS',          'type'=>'revenue',  'sub_type'=>'operating',      'parent_code'=>'4000'],
            ['code'=>'4003','name'=>'Other Revenue',             'type'=>'revenue',  'sub_type'=>'other',          'parent_code'=>'4000'],

            // ── EXPENSES ──────────────────────────────────────────────
            ['code'=>'5000','name'=>'Cost of Goods Sold',        'type'=>'expense',  'sub_type'=>'cogs'],
            ['code'=>'5001','name'=>'Fuel Purchase Cost',        'type'=>'expense',  'sub_type'=>'cogs',           'parent_code'=>'5000'],
            ['code'=>'5002','name'=>'Freight & Duty (Landed)',   'type'=>'expense',  'sub_type'=>'cogs',           'parent_code'=>'5000'],
            ['code'=>'5100','name'=>'Operating Expenses',        'type'=>'expense',  'sub_type'=>'operating'],
            ['code'=>'5101','name'=>'Transport & Freight',       'type'=>'expense',  'sub_type'=>'operating',      'parent_code'=>'5100'],
            ['code'=>'5102','name'=>'Depot Storage & Handling',  'type'=>'expense',  'sub_type'=>'operating',      'parent_code'=>'5100'],
            ['code'=>'5200','name'=>'General & Administrative',  'type'=>'expense',  'sub_type'=>'general'],
            ['code'=>'5201','name'=>'Office & Admin Expenses',   'type'=>'expense',  'sub_type'=>'general',        'parent_code'=>'5200'],
            ['code'=>'5202','name'=>'Staff & Payroll',           'type'=>'expense',  'sub_type'=>'general',        'parent_code'=>'5200'],
            ['code'=>'5203','name'=>'Border & Customs Costs',    'type'=>'expense',  'sub_type'=>'general',        'parent_code'=>'5200'],
        ];
    }
}
