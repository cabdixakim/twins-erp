<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Client;
use App\Models\ClientLedgerEntry;
use App\Models\TransporterLedgerEntry;
use App\Models\Transporter;
use App\Models\Purchase;
use App\Models\DepotStock;
use App\Models\Depot;
use App\Models\SupplierLedgerEntry;
use App\Models\DepotLedgerEntry;


class DashboardController extends Controller {
    public function index() {
        $cid = (int) auth()->user()->active_company_id;

        // ── Transporter payables ──────────────────────────────────
        $entries = TransporterLedgerEntry::where('company_id', $cid)
            ->selectRaw('transporter_id, currency, SUM(amount) as balance')
            ->groupBy('transporter_id', 'currency')
            ->havingRaw('SUM(amount) > 0')
            ->get();

        $byCurrency = $entries
            ->groupBy('currency')
            ->map(fn($rows) => $rows->sum('balance'))
            ->sortDesc();

        $topTransporters = collect();
        if ($entries->isNotEmpty()) {
            $topEntries      = $entries->sortByDesc('balance')->take(3);
            $transporterIds  = $topEntries->pluck('transporter_id')->unique();
            $transporterNames = Transporter::where('company_id', $cid)
                ->whereIn('id', $transporterIds)
                ->pluck('name', 'id');

            $topTransporters = $topEntries->map(fn($row) => (object)[
                'id'       => $row->transporter_id,
                'name'     => $transporterNames[$row->transporter_id] ?? 'Unknown',
                'balance'  => $row->balance,
                'currency' => $row->currency,
            ])->values();
        }

        // ── Open purchases ────────────────────────────────────────
        $openPurchasesCount = Purchase::where('company_id', $cid)
            ->whereIn('status', ['draft', 'confirmed', 'nominated'])
            ->count();

        $openByStatus = Purchase::where('company_id', $cid)
            ->whereIn('status', ['draft', 'confirmed', 'nominated'])
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        // ── Stock on hand ─────────────────────────────────────────
        $depotStockRows = DepotStock::where('depot_stocks.company_id', $cid)
            ->join('depots', 'depots.id', '=', 'depot_stocks.depot_id')
            ->where('depots.is_system', false)
            ->selectRaw('depots.id as depot_id, depots.name as depot_name, SUM(depot_stocks.qty_on_hand) as total_qty')
            ->groupBy('depots.id', 'depots.name')
            ->orderBy('depots.name')
            ->get();

        $totalStockOnHand = $depotStockRows->sum('total_qty');

        // ── Supplier payables ─────────────────────────────────────
        $supplierBalances = SupplierLedgerEntry::where('company_id', $cid)
            ->selectRaw('supplier_id, currency, SUM(amount) as balance')
            ->groupBy('supplier_id', 'currency')
            ->havingRaw('SUM(amount) > 0')
            ->get();

        $supplierByCurrency = $supplierBalances
            ->groupBy('currency')
            ->map(fn($rows) => $rows->sum('balance'))
            ->sortDesc();

        $supplierPayableTotal = $supplierByCurrency->sum();

        $topSuppliers = collect();
        if ($supplierBalances->isNotEmpty()) {
            $top    = $supplierBalances->sortByDesc('balance')->take(3);
            $sIds   = $top->pluck('supplier_id')->unique();
            $sNames = DB::table('suppliers')->where('company_id', $cid)->whereIn('id', $sIds)->pluck('name', 'id');
            $topSuppliers = $top->map(fn($r) => (object)[
                'id' => $r->supplier_id, 'name' => $sNames[$r->supplier_id] ?? 'Unknown',
                'balance' => $r->balance, 'currency' => $r->currency,
            ])->values();
        }

        // ── Depot payables ────────────────────────────────────────
        $depotBalances = DepotLedgerEntry::where('company_id', $cid)
            ->selectRaw('depot_id, currency, SUM(amount) as balance')
            ->groupBy('depot_id', 'currency')
            ->havingRaw('SUM(amount) > 0')
            ->get();

        $depotByCurrency = $depotBalances
            ->groupBy('currency')
            ->map(fn($rows) => $rows->sum('balance'))
            ->sortDesc();

        $depotPayableTotal = $depotByCurrency->sum();

        $topDepots = collect();
        if ($depotBalances->isNotEmpty()) {
            $top    = $depotBalances->sortByDesc('balance')->take(3);
            $dIds   = $top->pluck('depot_id')->unique();
            $dNames = Depot::where('company_id', $cid)->whereIn('id', $dIds)->pluck('name', 'id');
            $topDepots = $top->map(fn($r) => (object)[
                'id' => $r->depot_id, 'name' => $dNames[$r->depot_id] ?? 'Unknown',
                'balance' => $r->balance, 'currency' => $r->currency,
            ])->values();
        }

        // ── Client AR receivables ─────────────────────────────────
        $clientARBalances = ClientLedgerEntry::where('company_id', $cid)
            ->selectRaw('client_id, currency, SUM(amount) as balance')
            ->groupBy('client_id', 'currency')
            ->havingRaw('SUM(amount) > 0')
            ->get();

        $clientARByCurrency = $clientARBalances
            ->groupBy('currency')
            ->map(fn($rows) => $rows->sum('balance'))
            ->sortDesc();

        $totalAR = $clientARByCurrency->sum();

        $topARClients = collect();
        if ($clientARBalances->isNotEmpty()) {
            $top    = $clientARBalances->sortByDesc('balance')->take(3);
            $cIds   = $top->pluck('client_id')->unique();
            $cNames = Client::where('company_id', $cid)->whereIn('id', $cIds)->pluck('name', 'id');
            $topARClients = $top->map(fn($r) => (object)[
                'id' => $r->client_id, 'name' => $cNames[$r->client_id] ?? 'Unknown',
                'balance' => $r->balance, 'currency' => $r->currency,
            ])->values();
        }

        // ── Chart data: last 6 months throughput ─────────────────
        $from6m = now()->startOfMonth()->subMonths(5);

        $purchasedByMonth = DB::table('inventory_movements')
            ->where('company_id', $cid)
            ->where('type', 'receipt')
            ->where('ref_type', 'purchase')
            ->where('created_at', '>=', $from6m)
            ->selectRaw("TO_CHAR(created_at, 'Mon') as month_label, TO_CHAR(created_at, 'YYYY-MM') as month_key, SUM(qty) as qty")
            ->groupByRaw("TO_CHAR(created_at, 'Mon'), TO_CHAR(created_at, 'YYYY-MM')")
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        $soldByMonth = DB::table('inventory_movements')
            ->where('company_id', $cid)
            ->where('type', 'issue')
            ->where('ref_type', 'sale')
            ->where('created_at', '>=', $from6m)
            ->selectRaw("TO_CHAR(created_at, 'Mon') as month_label, TO_CHAR(created_at, 'YYYY-MM') as month_key, SUM(qty) as qty")
            ->groupByRaw("TO_CHAR(created_at, 'Mon'), TO_CHAR(created_at, 'YYYY-MM')")
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        // Build 6-month series
        $chartLabels    = [];
        $chartPurchased = [];
        $chartSold      = [];

        for ($i = 5; $i >= 0; $i--) {
            $key   = now()->startOfMonth()->subMonths($i)->format('Y-m');
            $label = now()->startOfMonth()->subMonths($i)->format('M');
            $chartLabels[]    = $label;
            $chartPurchased[] = round((float)($purchasedByMonth[$key]->qty ?? 0), 0);
            $chartSold[]      = round((float)($soldByMonth[$key]->qty ?? 0), 0);
        }

        // ── Bank balances (single JOIN — no N+1) ──────────────────
        $bankByCurrency  = collect();
        $topBankAccounts = collect();
        $bankData = DB::table('bank_accounts as ba')
            ->leftJoin('bank_transactions as bt', function ($j) {
                $j->on('bt.bank_account_id', '=', 'ba.id')
                  ->whereNull('bt.voided_at');
            })
            ->where('ba.company_id', $cid)
            ->where('ba.is_active', true)
            ->selectRaw("
                ba.id, ba.name, ba.currency, ba.opening_balance,
                ba.opening_balance + COALESCE(SUM(
                    CASE WHEN bt.type IN ('deposit','transfer_in') THEN bt.amount ELSE -bt.amount END
                ), 0) AS balance
            ")
            ->groupBy('ba.id', 'ba.name', 'ba.currency', 'ba.opening_balance')
            ->orderBy('ba.name')
            ->get();

        if ($bankData->isNotEmpty()) {
            $bankByCurrency = $bankData
                ->groupBy('currency')
                ->map(fn($rows) => $rows->sum('balance'))
                ->sortDesc();

            $topBankAccounts = $bankData->take(3)->values();
        }

        // AP vs AR summary
        $totalAP = $supplierPayableTotal + $depotPayableTotal + $byCurrency->sum();

        // ── Revenue & Gross Profit MTD ─────────────────────────────────────
        $revenueData = DB::table('sales')
            ->where('company_id', $cid)
            ->where('status', 'posted')
            ->whereBetween('sale_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->selectRaw('
                COALESCE(SUM(total), 0)        AS revenue_mtd,
                COALESCE(SUM(gross_profit), 0) AS gross_profit_mtd,
                COUNT(*)                        AS sales_count_mtd
            ')
            ->first();

        $revenueMtd     = (float) ($revenueData->revenue_mtd ?? 0);
        $grossProfitMtd = (float) ($revenueData->gross_profit_mtd ?? 0);
        $salesCountMtd  = (int) ($revenueData->sales_count_mtd ?? 0);
        $grossMarginPct = $revenueMtd > 0 ? round($grossProfitMtd / $revenueMtd * 100, 1) : 0;

        // ── Net Position (AR - AP) ─────────────────────────────────────────
        $netPosition = $totalAR - $totalAP;

        // ── Petty Cash float total ─────────────────────────────────────────
        $pettyCashRow = DB::table('petty_cash_transactions as pct')
            ->join('petty_cash_accounts as pca', 'pct.account_id', '=', 'pca.id')
            ->where('pca.company_id', $cid)
            ->selectRaw("COALESCE(SUM(CASE WHEN pct.type='top_up' THEN pct.amount ELSE -pct.amount END), 0) as total")
            ->first();
        $pettyCashTotal = (float) ($pettyCashRow->total ?? 0);

        // ── Base currency ──────────────────────────────────────────────────
        $baseCurrency = DB::table('companies')->where('id', $cid)->value('base_currency') ?? 'USD';
        $volumeUnit   = DB::table('companies')->where('id', $cid)->value('volume_unit') ?? 'L';

        return view('dashboard.index', compact(
            'byCurrency',
            'topTransporters',
            'openPurchasesCount',
            'openByStatus',
            'revenueMtd',
            'grossProfitMtd',
            'salesCountMtd',
            'grossMarginPct',
            'netPosition',
            'pettyCashTotal',
            'depotStockRows',
            'totalStockOnHand',
            'supplierByCurrency',
            'topSuppliers',
            'supplierPayableTotal',
            'depotByCurrency',
            'topDepots',
            'depotPayableTotal',
            'clientARByCurrency',
            'topARClients',
            'totalAR',
            'chartLabels',
            'chartPurchased',
            'chartSold',
            'totalAP',
            'bankByCurrency',
            'topBankAccounts',
            'baseCurrency',
            'volumeUnit'
        ));
    }
}
