<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Batch;
use App\Models\BatchCost;
use App\Models\Sale;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function cid(): int
    {
        return (int) auth()->user()->active_company_id;
    }

    public function index()
    {
        $cid = $this->cid();

        // Quick summary numbers for the hub
        $summary = [
            'open_invoices'    => Invoice::where('company_id', $cid)->whereIn('status', ['sent','partial','overdue'])->count(),
            'overdue_invoices' => Invoice::where('company_id', $cid)->where('status', 'overdue')
                ->orWhere(fn($q) => $q->where('company_id', $cid)->whereIn('status',['sent','partial'])->where('due_date','<',today()))
                ->count(),
            'total_batches'    => Batch::where('company_id', $cid)->count(),
            'active_batches'   => Batch::where('company_id', $cid)->where('status','active')->count(),
        ];

        return view('reports.index', compact('summary'));
    }

    /** Company-wide Profit & Loss for a period */
    public function profitAndLoss(Request $request)
    {
        $cid  = $this->cid();
        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to   = $request->input('to',   now()->toDateString());

        // ── Revenue & COGS from posted sales ─────────────────────────────
        $salesRow = DB::table('sales')
            ->where('company_id', $cid)
            ->where('status', 'posted')
            ->whereDate('posted_at', '>=', $from)
            ->whereDate('posted_at', '<=', $to)
            ->selectRaw('
                COALESCE(SUM(total), 0)       as revenue,
                COALESCE(SUM(cogs_total), 0)  as cogs,
                COALESCE(SUM(qty), 0)         as qty_sold
            ')
            ->first();

        $revenue = (float) $salesRow->revenue;
        $cogs    = (float) $salesRow->cogs;
        $qtySold = (float) $salesRow->qty_sold;

        $grossProfit    = $revenue - $cogs;
        $grossMarginPct = $revenue > 0 ? round($grossProfit / $revenue * 100, 1) : null;

        // ── Revenue by product ───────────────────────────────────────────
        $byProduct = DB::table('sales')
            ->join('products', 'products.id', '=', 'sales.product_id')
            ->where('sales.company_id', $cid)
            ->where('sales.status', 'posted')
            ->whereDate('sales.posted_at', '>=', $from)
            ->whereDate('sales.posted_at', '<=', $to)
            ->selectRaw('
                products.name as product_name,
                COALESCE(SUM(sales.qty), 0)        as qty,
                COALESCE(SUM(sales.total), 0)      as revenue,
                COALESCE(SUM(sales.cogs_total), 0) as cogs,
                COALESCE(SUM(sales.gross_profit), 0) as margin
            ')
            ->groupBy('products.name')
            ->orderByDesc('revenue')
            ->get();

        // COGS breakdown — split sales.cogs_total into purchase cost + per-category landed.
        // Denominator for import batches = SUM(qty_loaded) from import_trucks (rate × qty).
        // Falls back to qty_received / qty_purchased for local/cross-dock (no trucks).
        $cogsComponentsRaw = DB::select("
            SELECT bc.category,
                   SUM(
                       COALESCE(bc.amount_base, bc.amount)
                       / COALESCE(
                           NULLIF(
                               (SELECT SUM(it2.qty_loaded)
                                FROM import_trucks it2
                                JOIN import_nominations inn2 ON inn2.id = it2.nomination_id
                                WHERE inn2.purchase_id = bc.purchase_id
                                  AND it2.qty_loaded IS NOT NULL),
                               0
                           ),
                           NULLIF(b.qty_received, 0),
                           NULLIF(b.qty_purchased, 0)
                       )
                       * ic_agg.qty_consumed
                   ) AS total
            FROM batch_costs bc
            JOIN batches b ON b.id = bc.batch_id AND b.company_id = ?
            JOIN (
                SELECT batch_id, SUM(qty) AS qty_consumed
                FROM inventory_consumptions
                WHERE company_id = ?
                  AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY batch_id
            ) ic_agg ON ic_agg.batch_id = b.id
            WHERE bc.amount > 0
            GROUP BY bc.category
        ", [$cid, $cid, $from, $to]);

        $landedCategoryLabels = [
            'freight'       => 'Freight & Transport',
            'duty'          => 'Customs & Duty',
            'border_charge' => 'Border Charges',
            'hospitality'   => 'Hospitality',
            'storage'       => 'Storage',
            'penalty'       => 'Penalties',
            'other'         => 'Other Landed Costs',
        ];
        $landedComponentsMap   = collect($cogsComponentsRaw)->keyBy('category');
        $totalLandedComponents = collect($cogsComponentsRaw)->sum('total');
        $purchaseCostComponent = round((float) $cogs - (float) $totalLandedComponents, 2);

        $cogsBreakdown = collect();
        if ($purchaseCostComponent > 0.005) {
            $cogsBreakdown->push(['label' => 'Purchase Cost', 'amount' => $purchaseCostComponent]);
        }
        foreach ($landedCategoryLabels as $key => $label) {
            $amount = round((float) ($landedComponentsMap[$key]->total ?? 0), 2);
            if ($amount > 0.005) {
                $cogsBreakdown->push(['label' => $label, 'amount' => $amount]);
            }
        }

        // ── Depot charges in period ──────────────────────────────────────
        $depotCharges = 0.0;
        if (DB::getSchemaBuilder()->hasTable('depot_ledger_entries')) {
            $depotCharges = (float) DB::table('depot_ledger_entries')
                ->join('depots', 'depots.id', '=', 'depot_ledger_entries.depot_id')
                ->where('depots.company_id', $cid)
                ->whereIn('depot_ledger_entries.type', ['storage_charge','throughput_charge','loading_fee','other_charge'])
                ->whereDate('depot_ledger_entries.entry_date', '>=', $from)
                ->whereDate('depot_ledger_entries.entry_date', '<=', $to)
                ->sum('depot_ledger_entries.amount');
        }

        // ── Petty cash expenses in period ────────────────────────────────
        $pettyCash = 0.0;
        if (DB::getSchemaBuilder()->hasTable('petty_cash_transactions')) {
            $pettyCash = abs((float) DB::table('petty_cash_transactions')
                ->where('company_id', $cid)
                ->where('type', 'expense')
                ->whereDate('transaction_date', '>=', $from)
                ->whereDate('transaction_date', '<=', $to)
                ->sum('amount'));
        }

        // ── Transporter freight charges in period ────────────────────────
        // Import truck freight (ref_type = ImportTruck) is already captured in
        // batch_costs as a landed cost above — exclude it here to avoid double-counting.
        $transporterCharges = 0.0;
        if (DB::getSchemaBuilder()->hasTable('transporter_ledger_entries')) {
            $transporterCharges = (float) DB::table('transporter_ledger_entries')
                ->join('transporters', 'transporters.id', '=', 'transporter_ledger_entries.transporter_id')
                ->where('transporters.company_id', $cid)
                ->where('transporter_ledger_entries.type', 'freight_charge')
                ->where(function ($q) {
                    $q->whereNull('transporter_ledger_entries.ref_type')
                      ->orWhere('transporter_ledger_entries.ref_type', '!=', \App\Models\ImportTruck::class);
                })
                ->whereDate('transporter_ledger_entries.entry_date', '>=', $from)
                ->whereDate('transporter_ledger_entries.entry_date', '<=', $to)
                ->sum('transporter_ledger_entries.amount');
        }

        $grossProfit    = $revenue - $cogs;
        $grossMarginPct = $revenue > 0 ? round($grossProfit / $revenue * 100, 1) : null;
        $totalExpenses  = $depotCharges + $pettyCash + $transporterCharges;
        $netProfit      = $grossProfit - $totalExpenses;
        $netMarginPct   = $revenue > 0 ? round($netProfit / $revenue * 100, 1) : null;

        return view('reports.pl', compact(
            'from', 'to',
            'revenue', 'cogs', 'qtySold',
            'grossProfit', 'grossMarginPct',
            'cogsBreakdown',
            'depotCharges', 'pettyCash', 'transporterCharges',
            'totalExpenses',
            'netProfit', 'netMarginPct',
            'byProduct'
        ));
    }

    /** AR Aging report — open invoices bucketed by overdue days */
    public function arAging(Request $request)
    {
        $cid      = $this->cid();
        $asOf     = $request->input('as_of', today()->toDateString());
        $clientId = $request->input('client_id');

        $q = Invoice::where('company_id', $cid)
            ->whereNotIn('status', ['void', 'paid'])
            ->with('client')
            ->orderByDesc('due_date');

        if ($clientId) $q->where('client_id', $clientId);

        $invoices = $q->get();
        $asOfDate = \Carbon\Carbon::parse($asOf);

        // Bucket each invoice
        $invoices = $invoices->map(function ($inv) use ($asOfDate) {
            $daysOverdue = $inv->due_date
                ? (int) $inv->due_date->diffInDays($asOfDate, false)
                : 0;
            $inv->days_overdue = $daysOverdue;
            $inv->bucket = match(true) {
                $daysOverdue <= 0  => 'current',
                $daysOverdue <= 30 => '1_30',
                $daysOverdue <= 60 => '31_60',
                $daysOverdue <= 90 => '61_90',
                default            => '90_plus',
            };
            $inv->balance_due = max(0, (float)$inv->total - (float)$inv->paid_amount);
            return $inv;
        });

        // Group by client
        $byClient = $invoices->groupBy('client_id')->map(function ($group) {
            $client = $group->first()->client;
            return (object)[
                'client'   => $client,
                'current'  => $group->where('bucket','current')->sum('balance_due'),
                '1_30'     => $group->where('bucket','1_30')->sum('balance_due'),
                '31_60'    => $group->where('bucket','31_60')->sum('balance_due'),
                '61_90'    => $group->where('bucket','61_90')->sum('balance_due'),
                '90_plus'  => $group->where('bucket','90_plus')->sum('balance_due'),
                'total'    => $group->sum('balance_due'),
                'invoices' => $group,
                'currency' => $group->first()->currency ?? 'USD',
            ];
        })->sortByDesc('total')->values();

        $grandTotal = [
            'current' => $byClient->sum('current'),
            '1_30'    => $byClient->sum('1_30'),
            '31_60'   => $byClient->sum('31_60'),
            '61_90'   => $byClient->sum('61_90'),
            '90_plus' => $byClient->sum('90_plus'),
            'total'   => $byClient->sum('total'),
        ];

        $clients = Client::where('company_id', $cid)->orderBy('name')->get();

        return view('reports.ar-aging', compact('byClient', 'grandTotal', 'asOf', 'clients'));
    }

    /** AP Aging — outstanding supplier + transporter payables bucketed by age */
    public function apAging(Request $request)
    {
        $cid  = $this->cid();
        $asOf = $request->input('as_of', today()->toDateString());

        // ── Supplier AP ──────────────────────────────────────────────────
        // Net balance = sum of all entries per supplier (invoices positive, payments negative)
        $supplierEntries = DB::table('supplier_ledger_entries')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_ledger_entries.supplier_id')
            ->where('suppliers.company_id', $cid)
            ->whereDate('supplier_ledger_entries.entry_date', '<=', $asOf)
            ->selectRaw('
                supplier_ledger_entries.supplier_id,
                suppliers.name as supplier_name,
                SUM(supplier_ledger_entries.amount) as balance,
                MIN(CASE WHEN supplier_ledger_entries.type = \'purchase_invoice\' THEN supplier_ledger_entries.entry_date ELSE NULL END) as oldest_invoice
            ')
            ->groupBy('supplier_ledger_entries.supplier_id', 'suppliers.name')
            ->having(DB::raw('SUM(supplier_ledger_entries.amount)'), '>', 0)
            ->orderByDesc('balance')
            ->get();

        $asOfDate = \Carbon\Carbon::parse($asOf);

        $supplierRows = $supplierEntries->map(function ($row) use ($asOfDate) {
            $daysAge = $row->oldest_invoice
                ? (int) \Carbon\Carbon::parse($row->oldest_invoice)->diffInDays($asOfDate)
                : 0;

            return (object)[
                'name'    => $row->supplier_name,
                'balance' => round((float)$row->balance, 2),
                'days'    => $daysAge,
                'bucket'  => match(true) {
                    $daysAge <= 30  => 'current',
                    $daysAge <= 60  => '31_60',
                    $daysAge <= 90  => '61_90',
                    default         => '90_plus',
                },
            ];
        });

        // ── Transporter AP ────────────────────────────────────────────────
        $transporterEntries = DB::table('transporter_ledger_entries')
            ->join('transporters', 'transporters.id', '=', 'transporter_ledger_entries.transporter_id')
            ->where('transporters.company_id', $cid)
            ->whereDate('transporter_ledger_entries.entry_date', '<=', $asOf)
            ->selectRaw("
                transporter_ledger_entries.transporter_id,
                transporters.name as transporter_name,
                SUM(CASE WHEN transporter_ledger_entries.type IN ('freight_charge','advance') THEN transporter_ledger_entries.amount ELSE -transporter_ledger_entries.amount END) as balance,
                MIN(CASE WHEN transporter_ledger_entries.type = 'freight_charge' THEN transporter_ledger_entries.entry_date ELSE NULL END) as oldest_charge
            ")
            ->groupBy('transporter_ledger_entries.transporter_id', 'transporters.name')
            ->having(DB::raw("SUM(CASE WHEN transporter_ledger_entries.type IN ('freight_charge','advance') THEN transporter_ledger_entries.amount ELSE -transporter_ledger_entries.amount END)"), '>', 0)
            ->orderByDesc('balance')
            ->get();

        $transporterRows = $transporterEntries->map(function ($row) use ($asOfDate) {
            $daysAge = $row->oldest_charge
                ? (int) \Carbon\Carbon::parse($row->oldest_charge)->diffInDays($asOfDate)
                : 0;

            return (object)[
                'name'    => $row->transporter_name,
                'balance' => round((float)$row->balance, 2),
                'days'    => $daysAge,
                'bucket'  => match(true) {
                    $daysAge <= 30  => 'current',
                    $daysAge <= 60  => '31_60',
                    $daysAge <= 90  => '61_90',
                    default         => '90_plus',
                },
            ];
        });

        // Depot payables
        $depotEntries = DB::table('depot_ledger_entries')
            ->join('depots', 'depots.id', '=', 'depot_ledger_entries.depot_id')
            ->where('depots.company_id', $cid)
            ->where('depots.is_system', false)
            ->whereDate('depot_ledger_entries.created_at', '<=', $asOf)
            ->selectRaw("
                depot_ledger_entries.depot_id,
                depots.name as depot_name,
                SUM(CASE WHEN depot_ledger_entries.type IN ('storage_charge','handling_fee','loading_fee','other_charge') THEN depot_ledger_entries.amount ELSE -depot_ledger_entries.amount END) as balance,
                MIN(CASE WHEN depot_ledger_entries.type IN ('storage_charge','handling_fee','loading_fee','other_charge') THEN depot_ledger_entries.created_at ELSE NULL END) as oldest_charge
            ")
            ->groupBy('depot_ledger_entries.depot_id', 'depots.name')
            ->having(DB::raw("SUM(CASE WHEN depot_ledger_entries.type IN ('storage_charge','handling_fee','loading_fee','other_charge') THEN depot_ledger_entries.amount ELSE -depot_ledger_entries.amount END)"), '>', 0)
            ->orderByDesc('balance')
            ->get();

        $depotRows = $depotEntries->map(function ($row) use ($asOfDate) {
            $daysAge = $row->oldest_charge
                ? (int) \Carbon\Carbon::parse($row->oldest_charge)->diffInDays($asOfDate)
                : 0;

            return (object)[
                'name'    => $row->depot_name,
                'balance' => round((float)$row->balance, 2),
                'days'    => $daysAge,
                'bucket'  => match(true) {
                    $daysAge <= 30  => 'current',
                    $daysAge <= 60  => '31_60',
                    $daysAge <= 90  => '61_90',
                    default         => '90_plus',
                },
            ];
        });

        $grandTotals = [
            'supplier'    => $supplierRows->sum('balance'),
            'transporter' => $transporterRows->sum('balance'),
            'depot'       => $depotRows->sum('balance'),
            'total'       => $supplierRows->sum('balance') + $transporterRows->sum('balance') + $depotRows->sum('balance'),
        ];

        return view('reports.ap-aging', compact(
            'supplierRows', 'transporterRows', 'depotRows', 'grandTotals', 'asOf'
        ));
    }

    /** Volume throughput — fuel received into depots (net of shrinkage) vs. sales posted, by month */
    public function throughput(Request $request)
    {
        $cid    = $this->cid();
        $months = (int)($request->input('months', 6));
        $months = max(3, min($months, 24));

        $from = now()->startOfMonth()->subMonths($months - 1);

        // Monthly receipts into REAL depots (excludes the virtual CROSS DOCK depot),
        // bucketed by the movement date (when it actually landed), not the PO date.
        $grossReceiptsRaw = DB::table('inventory_movements as im')
            ->join('depots as d', 'd.id', '=', 'im.to_depot_id')
            ->where('im.company_id', $cid)
            ->where('im.type', 'receipt')
            ->whereIn('im.ref_type', ['purchase', 'import_truck'])
            ->where('d.is_system', false)
            ->whereDate('im.created_at', '>=', $from)
            ->selectRaw("TO_CHAR(im.created_at, 'YYYY-MM') as month, SUM(im.qty) as qty, COUNT(DISTINCT im.ref_id) as count")
            ->groupByRaw("TO_CHAR(im.created_at, 'YYYY-MM')")
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Shrinkage / loss adjustments applied against those same depot receipts
        // (depot_shrinkage, write_off, meter_variance, stock_count_correction, transit_loss, etc.)
        $shrinkageRaw = DB::table('inventory_movements as im')
            ->join('depots as d', 'd.id', '=', 'im.from_depot_id')
            ->where('im.company_id', $cid)
            ->where('im.type', 'adjustment')
            ->where('im.qty', '<', 0)
            ->whereIn('im.ref_type', ['purchase', 'import_truck'])
            ->where('d.is_system', false)
            ->whereDate('im.created_at', '>=', $from)
            ->selectRaw("TO_CHAR(im.created_at, 'YYYY-MM') as month, SUM(ABS(im.qty)) as qty")
            ->groupByRaw("TO_CHAR(im.created_at, 'YYYY-MM')")
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Monthly sales posted (qty)
        $salesRaw = DB::table('inventory_movements')
            ->where('company_id', $cid)
            ->where('type', 'issue')
            ->where('ref_type', 'sale')
            ->whereDate('created_at', '>=', $from)
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month, SUM(qty) as qty, COUNT(DISTINCT ref_id) as count")
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM')")
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Monthly revenue
        $revenueRaw = DB::table('sales')
            ->where('company_id', $cid)
            ->where('status', 'posted')
            ->whereDate('created_at', '>=', $from)
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month, SUM(total) as revenue")
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM')")
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // Build month series
        $series = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key       = now()->startOfMonth()->subMonths($i)->format('Y-m');
            $label     = now()->startOfMonth()->subMonths($i)->format('M Y');
            $grossQty  = (float)($grossReceiptsRaw[$key]->qty ?? 0);
            $shrinkage = (float)($shrinkageRaw[$key]->qty ?? 0);
            $series[] = [
                'month'            => $key,
                'label'            => $label,
                'received_qty'     => round($grossQty - $shrinkage, 0),
                'shrinkage_qty'    => round($shrinkage, 0),
                'received_count'   => (int)($grossReceiptsRaw[$key]->count ?? 0),
                'sold_qty'         => round((float)($salesRaw[$key]->qty ?? 0), 0),
                'sold_count'       => (int)($salesRaw[$key]->count ?? 0),
                'revenue'          => round((float)($revenueRaw[$key]->revenue ?? 0), 2),
            ];
        }

        // Summary totals
        $totals = [
            'received_qty'    => array_sum(array_column($series, 'received_qty')),
            'shrinkage_qty'   => array_sum(array_column($series, 'shrinkage_qty')),
            'sold_qty'        => array_sum(array_column($series, 'sold_qty')),
            'revenue'         => array_sum(array_column($series, 'revenue')),
            'received_count'  => array_sum(array_column($series, 'received_count')),
            'sold_count'      => array_sum(array_column($series, 'sold_count')),
        ];

        return view('reports.throughput', compact('series', 'totals', 'months'));
    }

    /** Inventory Position — period movement summary (with $ values) + live pipeline + loss reconciliation */
    public function inventoryPosition(Request $request)
    {
        $cid  = $this->cid();
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to',   today()->toDateString());

        $currency = DB::table('companies')->where('id', $cid)->value('base_currency') ?? '';

        // ── Period stock movement by product ─────────────────────────────
        // Opening = cumulative net before $from
        $openingRaw = DB::table('inventory_movements')
            ->where('company_id', $cid)
            ->whereDate('created_at', '<', $from)
            ->selectRaw("
                product_id,
                SUM(CASE WHEN type='receipt'    THEN qty
                         WHEN type='issue'      THEN -qty
                         WHEN type='adjustment' THEN qty
                         ELSE 0 END) as qty,
                SUM(CASE WHEN type='receipt'    THEN COALESCE(total_cost, qty * unit_cost)
                         WHEN type='issue'      THEN -COALESCE(total_cost, qty * unit_cost)
                         WHEN type='adjustment' THEN COALESCE(total_cost, qty * unit_cost)
                         ELSE 0 END) as value
            ")
            ->groupBy('product_id')
            ->get()->keyBy('product_id');

        // Movements within the period
        $periodRaw = DB::table('inventory_movements')
            ->where('company_id', $cid)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->selectRaw("
                product_id,
                SUM(CASE WHEN type='receipt'    THEN qty ELSE 0 END) as receipts,
                SUM(CASE WHEN type='receipt'    THEN COALESCE(total_cost, qty * unit_cost) ELSE 0 END) as receipts_value,
                SUM(CASE WHEN type='issue'      THEN qty ELSE 0 END) as dispatched,
                SUM(CASE WHEN type='issue'      THEN COALESCE(total_cost, qty * unit_cost) ELSE 0 END) as dispatched_value,
                SUM(CASE WHEN type='adjustment' AND qty < 0 THEN ABS(qty) ELSE 0 END) as losses,
                SUM(CASE WHEN type='adjustment' AND qty < 0 THEN ABS(COALESCE(total_cost, qty * unit_cost)) ELSE 0 END) as losses_value,
                SUM(CASE WHEN type='adjustment' AND qty > 0 THEN qty ELSE 0 END) as adjustments_in,
                SUM(CASE WHEN type='adjustment' AND qty > 0 THEN COALESCE(total_cost, qty * unit_cost) ELSE 0 END) as adjustments_in_value
            ")
            ->groupBy('product_id')
            ->get()->keyBy('product_id');

        // Loss reconciliation from inventory_adjustments (authoritative loss ledger:
        // recoverable vs non-recoverable), within the period
        $lossAdjRaw = DB::table('inventory_adjustments')
            ->where('company_id', $cid)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->selectRaw("
                product_id,
                SUM(CASE WHEN recoverable THEN qty ELSE 0 END) as recoverable_qty,
                SUM(CASE WHEN recoverable THEN total_value ELSE 0 END) as recoverable_value,
                SUM(CASE WHEN NOT recoverable THEN qty ELSE 0 END) as non_recoverable_qty,
                SUM(CASE WHEN NOT recoverable THEN total_value ELSE 0 END) as non_recoverable_value
            ")
            ->groupBy('product_id')
            ->get()->keyBy('product_id');

        // Product names
        $products = DB::table('products')
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');

        // Collect all product ids that appear in any movement
        $allProductIds = collect($openingRaw->keys())
            ->merge($periodRaw->keys())
            ->unique()->values();

        $movementRows = [];
        foreach ($allProductIds as $pid) {
            $opening       = (float)($openingRaw[$pid]->qty ?? 0);
            $openingValue  = (float)($openingRaw[$pid]->value ?? 0);
            $receipts      = (float)($periodRaw[$pid]->receipts ?? 0);
            $receiptsValue = (float)($periodRaw[$pid]->receipts_value ?? 0);
            $dispatched      = (float)($periodRaw[$pid]->dispatched ?? 0);
            $dispatchedValue = (float)($periodRaw[$pid]->dispatched_value ?? 0);
            $losses      = (float)($periodRaw[$pid]->losses ?? 0);
            $lossesValue = (float)($periodRaw[$pid]->losses_value ?? 0);
            $adjIn      = (float)($periodRaw[$pid]->adjustments_in ?? 0);
            $adjInValue = (float)($periodRaw[$pid]->adjustments_in_value ?? 0);
            $closing      = $opening + $receipts - $dispatched - $losses + $adjIn;
            $closingValue = $openingValue + $receiptsValue - $dispatchedValue - $lossesValue + $adjInValue;
            $closingAvgCost = abs($closing) > 0.0005 ? $closingValue / $closing : 0.0;

            $recoverableQty      = (float)($lossAdjRaw[$pid]->recoverable_qty ?? 0);
            $recoverableValue    = (float)($lossAdjRaw[$pid]->recoverable_value ?? 0);
            $nonRecoverableQty   = (float)($lossAdjRaw[$pid]->non_recoverable_qty ?? 0);
            $nonRecoverableValue = (float)($lossAdjRaw[$pid]->non_recoverable_value ?? 0);

            $movementRows[] = [
                'product_id'    => $pid,
                'product'       => $products[$pid] ?? "Product #$pid",
                'opening'       => round($opening, 3),
                'opening_value' => round($openingValue, 2),
                'receipts'       => round($receipts, 3),
                'receipts_value' => round($receiptsValue, 2),
                'dispatched'       => round($dispatched, 3),
                'dispatched_value' => round($dispatchedValue, 2),
                'losses'       => round($losses, 3),
                'losses_value' => round($lossesValue, 2),
                'adj_in'       => round($adjIn, 3),
                'adj_in_value' => round($adjInValue, 2),
                'closing'         => round($closing, 3),
                'closing_value'   => round($closingValue, 2),
                'closing_avg_cost' => round($closingAvgCost, 4),
                'recoverable_qty'        => round($recoverableQty, 3),
                'recoverable_value'      => round($recoverableValue, 2),
                'non_recoverable_qty'    => round($nonRecoverableQty, 3),
                'non_recoverable_value'  => round($nonRecoverableValue, 2),
            ];
        }

        // ── Per-purchase breakdown within the period ──────────────────────
        // Every receipt movement in the period, joined back to its purchase
        $purchaseBreakdown = DB::table('inventory_movements as m')
            ->join('purchases as p', function ($j) {
                $j->on('p.id', '=', 'm.ref_id')->where('m.ref_type', '=', 'purchase');
            })
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->where('m.company_id', $cid)
            ->where('m.type', 'receipt')
            ->whereDate('m.created_at', '>=', $from)
            ->whereDate('m.created_at', '<=', $to)
            ->selectRaw("
                p.id as purchase_id,
                p.reference,
                p.type as purchase_type,
                s.name as supplier_name,
                m.product_id,
                m.created_at,
                SUM(m.qty) as qty,
                SUM(COALESCE(m.total_cost, m.qty * m.unit_cost)) as value,
                AVG(m.unit_cost) as avg_unit_cost
            ")
            ->groupBy('p.id', 'p.reference', 'p.type', 's.name', 'm.product_id', 'm.created_at')
            ->orderByDesc('m.created_at')
            ->get()
            ->map(function ($row) use ($products) {
                $row->product_name = $products[$row->product_id] ?? "Product #{$row->product_id}";
                return $row;
            });

        // ── Live pipeline (current, not period-bound) ─────────────────────
        // At shipper — purchased qty not yet loaded onto a truck, on active import purchases.
        // Mirrors the per-purchase "Remaining at shipper" calc: purchase.qty - SUM(qty_loaded)
        // across trucks that have actually loaded (excludes 'nominated' and 'loading_failed' trucks,
        // since loading_failed capacity is still sitting at the shipper).
        $importPurchasesRaw = DB::table('purchases')
            ->where('purchases.company_id', $cid)
            ->whereIn('purchases.status', ['confirmed', 'nominated'])
            ->where('purchases.type', 'import')
            ->leftJoin('import_nominations', 'import_nominations.purchase_id', '=', 'purchases.id')
            ->leftJoin('import_trucks', function ($j) {
                $j->on('import_trucks.nomination_id', '=', 'import_nominations.id')
                  ->whereNotIn('import_trucks.status', ['nominated', 'loading_failed']);
            })
            ->selectRaw('
                purchases.id as purchase_id,
                purchases.product_id,
                purchases.qty as purchase_qty,
                purchases.unit_price,
                COALESCE(SUM(import_trucks.qty_loaded), 0) as qty_loaded
            ')
            ->groupBy('purchases.id', 'purchases.product_id', 'purchases.qty', 'purchases.unit_price')
            ->get();

        $atShipperByProductRaw = [];
        foreach ($importPurchasesRaw as $p) {
            $remaining = max(0.0, (float) $p->purchase_qty - (float) $p->qty_loaded);
            if ($remaining <= 0) continue;
            $pid = $p->product_id;
            if (!isset($atShipperByProductRaw[$pid])) {
                $atShipperByProductRaw[$pid] = (object) ['product_id' => $pid, 'qty' => 0.0, 'value' => 0.0, 'n' => 0];
            }
            $atShipperByProductRaw[$pid]->qty   += $remaining;
            $atShipperByProductRaw[$pid]->value += $remaining * (float) $p->unit_price;
            $atShipperByProductRaw[$pid]->n     += 1;
        }
        $atShipperRaw = collect(array_values($atShipperByProductRaw));

        // In transit — trucks loaded/moving but not yet delivered
        $inTransitRaw = DB::table('import_trucks')
            ->join('import_nominations', 'import_nominations.id', '=', 'import_trucks.nomination_id')
            ->join('purchases', 'purchases.id', '=', 'import_nominations.purchase_id')
            ->where('import_trucks.company_id', $cid)
            ->whereIn('import_trucks.status', ['loaded', 'in_transit', 'border_cleared'])
            ->whereNotNull('import_trucks.qty_loaded')
            ->selectRaw('purchases.product_id, SUM(import_trucks.qty_loaded) as qty, SUM(import_trucks.qty_loaded * purchases.unit_price) as value, COUNT(import_trucks.id) as trucks')
            ->groupBy('purchases.product_id')
            ->get();

        // In depots — current depot stock (exclude CROSS DOCK system depot)
        $inDepotsRaw = DB::table('depot_stocks')
            ->join('depots', 'depots.id', '=', 'depot_stocks.depot_id')
            ->where('depot_stocks.company_id', $cid)
            ->where('depots.is_system', false)
            ->where('depot_stocks.qty_on_hand', '>', 0)
            ->selectRaw('depot_stocks.product_id, depots.name as depot_name, SUM(depot_stocks.qty_on_hand) as qty, SUM(depot_stocks.qty_on_hand * depot_stocks.unit_cost) as value')
            ->groupBy('depot_stocks.product_id', 'depots.id', 'depots.name')
            ->orderBy('depots.name')
            ->get();

        // Sold to clients (all time)
        $soldRaw = DB::table('sales')
            ->where('company_id', $cid)
            ->where('status', 'posted')
            ->selectRaw('product_id, SUM(qty) as qty, SUM(total) as value, COUNT(*) as n')
            ->groupBy('product_id')
            ->get();

        // Total losses ever, from the authoritative loss ledger — split recoverable / non-recoverable
        $lossesRaw = DB::table('inventory_adjustments')
            ->where('company_id', $cid)
            ->selectRaw('
                product_id,
                SUM(qty) as qty,
                SUM(total_value) as value,
                SUM(CASE WHEN recoverable THEN qty ELSE 0 END) as recoverable_qty,
                SUM(CASE WHEN recoverable THEN total_value ELSE 0 END) as recoverable_value,
                SUM(CASE WHEN NOT recoverable THEN qty ELSE 0 END) as non_recoverable_qty,
                SUM(CASE WHEN NOT recoverable THEN total_value ELSE 0 END) as non_recoverable_value
            ')
            ->groupBy('product_id')
            ->get();

        // Build pipeline summary per product
        $pipelineProductIds = collect()
            ->merge($atShipperRaw->pluck('product_id'))
            ->merge($inTransitRaw->pluck('product_id'))
            ->merge($inDepotsRaw->pluck('product_id'))
            ->merge($soldRaw->pluck('product_id'))
            ->merge($lossesRaw->pluck('product_id'))
            ->unique()->values();

        $atShipperByProduct  = $atShipperRaw->keyBy('product_id');
        $inTransitByProduct  = $inTransitRaw->keyBy('product_id');
        $inDepotsByProduct   = $inDepotsRaw->groupBy('product_id');
        $soldByProduct       = $soldRaw->keyBy('product_id');
        $lossesByProduct     = $lossesRaw->keyBy('product_id');

        $pipelineRows = [];
        foreach ($pipelineProductIds as $pid) {
            $inDepotQty   = $inDepotsByProduct->get($pid, collect())->sum('qty');
            $inDepotValue = $inDepotsByProduct->get($pid, collect())->sum('value');
            $pipelineRows[] = [
                'product'    => $products[$pid] ?? "Product #$pid",
                'at_shipper'       => round((float)($atShipperByProduct[$pid]->qty ?? 0), 3),
                'at_shipper_value' => round((float)($atShipperByProduct[$pid]->value ?? 0), 2),
                'in_transit'       => round((float)($inTransitByProduct[$pid]->qty ?? 0), 3),
                'in_transit_value' => round((float)($inTransitByProduct[$pid]->value ?? 0), 2),
                'in_depots'        => round((float)$inDepotQty, 3),
                'in_depots_value'  => round((float)$inDepotValue, 2),
                'sold'             => round((float)($soldByProduct[$pid]->qty ?? 0), 3),
                'sold_value'       => round((float)($soldByProduct[$pid]->value ?? 0), 2),
                'losses'     => round((float)($lossesByProduct[$pid]->qty ?? 0), 3),
                'losses_value'           => round((float)($lossesByProduct[$pid]->value ?? 0), 2),
                'losses_recoverable'     => round((float)($lossesByProduct[$pid]->recoverable_qty ?? 0), 3),
                'losses_recoverable_value' => round((float)($lossesByProduct[$pid]->recoverable_value ?? 0), 2),
                'losses_non_recoverable' => round((float)($lossesByProduct[$pid]->non_recoverable_qty ?? 0), 3),
                'losses_non_recoverable_value' => round((float)($lossesByProduct[$pid]->non_recoverable_value ?? 0), 2),
            ];
        }

        // Pipeline totals
        $pipelineTotals = [
            'at_shipper'       => collect($pipelineRows)->sum('at_shipper'),
            'at_shipper_value' => collect($pipelineRows)->sum('at_shipper_value'),
            'in_transit'       => collect($pipelineRows)->sum('in_transit'),
            'in_transit_value' => collect($pipelineRows)->sum('in_transit_value'),
            'in_depots'        => collect($pipelineRows)->sum('in_depots'),
            'in_depots_value'  => collect($pipelineRows)->sum('in_depots_value'),
            'sold'             => collect($pipelineRows)->sum('sold'),
            'sold_value'       => collect($pipelineRows)->sum('sold_value'),
            'losses'     => collect($pipelineRows)->sum('losses'),
            'losses_value'                 => collect($pipelineRows)->sum('losses_value'),
            'losses_recoverable'           => collect($pipelineRows)->sum('losses_recoverable'),
            'losses_recoverable_value'     => collect($pipelineRows)->sum('losses_recoverable_value'),
            'losses_non_recoverable'       => collect($pipelineRows)->sum('losses_non_recoverable'),
            'losses_non_recoverable_value' => collect($pipelineRows)->sum('losses_non_recoverable_value'),
        ];

        // Depot breakdown (for "in depot" section)
        $depotBreakdown = $inDepotsRaw;

        return view('reports.inventory-position', compact(
            'from', 'to', 'currency',
            'movementRows', 'pipelineRows', 'pipelineTotals',
            'depotBreakdown', 'products', 'purchaseBreakdown'
        ));
    }

    /* ------------------------------------------------------------------ */
    /*  CSV Exports                                                          */
    /* ------------------------------------------------------------------ */

    public function exportPl(Request $request)
    {
        $cid  = $this->cid();
        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to   = $request->input('to', today()->toDateString());

        $revenue = (float) DB::table('sales')
            ->where('company_id', $cid)->where('status', 'posted')
            ->whereDate('posted_at', '>=', $from)->whereDate('posted_at', '<=', $to)
            ->sum('total');

        $revenueByProduct = DB::table('sales')
            ->join('products', 'products.id', '=', 'sales.product_id')
            ->where('sales.company_id', $cid)->where('sales.status', 'posted')
            ->whereDate('sales.posted_at', '>=', $from)->whereDate('sales.posted_at', '<=', $to)
            ->selectRaw('products.name as product_name, SUM(sales.total) as revenue, SUM(sales.qty) as qty, SUM(sales.cogs_total) as cogs')
            ->groupBy('products.name')->orderByDesc('revenue')->get();

        $cogs = $revenueByProduct->sum('cogs');

        $cogsComponentsRaw = DB::select("
            SELECT bc.category, SUM(
                COALESCE(bc.amount_base,bc.amount)
                / COALESCE(
                    NULLIF((SELECT SUM(it2.qty_loaded) FROM import_trucks it2
                            JOIN import_nominations inn2 ON inn2.id=it2.nomination_id
                            WHERE inn2.purchase_id=bc.purchase_id AND it2.qty_loaded IS NOT NULL),0),
                    NULLIF(b.qty_received,0),NULLIF(b.qty_purchased,0))
                * ic_agg.qty_consumed
            ) AS total
            FROM batch_costs bc
            JOIN batches b ON b.id=bc.batch_id AND b.company_id=?
            JOIN (
                SELECT batch_id, SUM(qty) AS qty_consumed
                FROM inventory_consumptions
                WHERE company_id=? AND DATE(created_at) BETWEEN ? AND ?
                GROUP BY batch_id
            ) ic_agg ON ic_agg.batch_id=b.id
            WHERE bc.amount>0 GROUP BY bc.category
        ", [$cid, $cid, $from, $to]);
        $exportLandedLabels  = ['freight'=>'Freight & Transport','duty'=>'Customs & Duty','border_charge'=>'Border Charges','hospitality'=>'Hospitality','storage'=>'Storage','penalty'=>'Penalties','other'=>'Other Landed Costs'];
        $exportLandedMap     = collect($cogsComponentsRaw)->keyBy('category');
        $exportLandedTotal   = collect($cogsComponentsRaw)->sum('total');
        $exportPurchaseCost  = round($cogs - $exportLandedTotal, 2);
        $exportCogsBreakdown = collect();
        if ($exportPurchaseCost > 0.005) {
            $exportCogsBreakdown->push(['label' => 'Purchase Cost', 'amount' => $exportPurchaseCost]);
        }
        foreach ($exportLandedLabels as $key => $label) {
            $amt = round((float)($exportLandedMap[$key]->total ?? 0), 2);
            if ($amt > 0.005) $exportCogsBreakdown->push(['label' => $label, 'amount' => $amt]);
        }

        $grossProfit = $revenue - $cogs;

        $transporterCharges = (float) DB::table('transporter_ledger_entries')
            ->join('transporters', 'transporters.id', '=', 'transporter_ledger_entries.transporter_id')
            ->where('transporters.company_id', $cid)
            ->where('transporter_ledger_entries.type', 'freight_charge')
            ->where(function ($q) {
                $q->whereNull('transporter_ledger_entries.ref_type')
                  ->orWhere('transporter_ledger_entries.ref_type', '!=', \App\Models\ImportTruck::class);
            })
            ->whereDate('transporter_ledger_entries.entry_date', '>=', $from)
            ->whereDate('transporter_ledger_entries.entry_date', '<=', $to)
            ->sum('transporter_ledger_entries.amount');

        $depotCharges = (float) DB::table('depot_ledger_entries')
            ->join('depots', 'depots.id', '=', 'depot_ledger_entries.depot_id')
            ->where('depots.company_id', $cid)
            ->whereDate('depot_ledger_entries.entry_date', '>=', $from)
            ->whereDate('depot_ledger_entries.entry_date', '<=', $to)
            ->whereIn('depot_ledger_entries.type', ['storage_charge', 'handling_fee', 'loading_fee', 'other_charge'])
            ->sum('depot_ledger_entries.amount');

        $pettyCash = (float) DB::table('petty_cash_transactions')
            ->join('petty_cash_accounts', 'petty_cash_accounts.id', '=', 'petty_cash_transactions.account_id')
            ->where('petty_cash_accounts.company_id', $cid)
            ->where('petty_cash_transactions.type', 'expense')
            ->whereDate('petty_cash_transactions.transaction_date', '>=', $from)
            ->whereDate('petty_cash_transactions.transaction_date', '<=', $to)
            ->sum(DB::raw('ABS(petty_cash_transactions.amount)'));

        $totalOpex = $transporterCharges + $depotCharges + $pettyCash;
        $netProfit = $grossProfit - $totalOpex;
        $filename  = "pl-report-{$from}-to-{$to}.csv";

        return response()->streamDownload(function () use (
            $revenueByProduct, $revenue, $cogs, $exportCogsBreakdown,
            $grossProfit, $transporterCharges, $depotCharges, $pettyCash,
            $totalOpex, $netProfit, $from, $to
        ) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ["Profit & Loss: {$from} to {$to}"]);
            fputcsv($out, []);
            fputcsv($out, ['Section', 'Product / Line', 'Qty (L)', 'Revenue', 'COGS']);
            foreach ($revenueByProduct as $row) {
                fputcsv($out, ['Revenue', $row->product_name,
                    number_format((float)$row->qty, 0, '.', ''),
                    number_format((float)$row->revenue, 2, '.', ''),
                    number_format((float)$row->cogs, 2, '.', ''),
                ]);
            }
            fputcsv($out, []);
            foreach ($exportCogsBreakdown as $line) {
                fputcsv($out, ['Cost of Sales', $line['label'], '', '', number_format($line['amount'], 2, '.', '')]);
            }
            fputcsv($out, ['Cost of Sales', 'Total Cost of Sales', '', '', number_format($cogs, 2, '.', '')]);
            fputcsv($out, ['', 'GROSS PROFIT', '', '', number_format($grossProfit, 2, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['Operating Expenses', 'Transport & Freight', '', '', number_format($transporterCharges, 2, '.', '')]);
            fputcsv($out, ['Operating Expenses', 'Depot Charges', '', '', number_format($depotCharges, 2, '.', '')]);
            fputcsv($out, ['Operating Expenses', 'Petty Cash Expenses', '', '', number_format($pettyCash, 2, '.', '')]);
            fputcsv($out, ['', 'Total Operating Expenses', '', '', number_format($totalOpex, 2, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['', 'NET PROFIT', '', '', number_format($netProfit, 2, '.', '')]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportArAging(Request $request)
    {
        $cid      = $this->cid();
        $asOf     = $request->input('as_of', today()->toDateString());
        $clientId = $request->input('client_id');
        $asOfDate = \Carbon\Carbon::parse($asOf);

        $q = \App\Models\Invoice::where('company_id', $cid)
            ->whereNotIn('status', ['void','paid'])->with('client')->orderByDesc('due_date');
        if ($clientId) $q->where('client_id', $clientId);

        $rows = $q->get()->map(function ($inv) use ($asOfDate) {
            $days = $inv->due_date ? (int)$inv->due_date->diffInDays($asOfDate, false) : 0;
            $inv->days_overdue = $days;
            $inv->bucket = match(true) {
                $days <= 0  => 'Current',
                $days <= 30 => '1-30 days',
                $days <= 60 => '31-60 days',
                $days <= 90 => '61-90 days',
                default     => '90+ days',
            };
            $inv->balance_due = max(0, (float)$inv->total - (float)$inv->paid_amount);
            return $inv;
        });

        $filename = "ar-aging-{$asOf}.csv";

        return response()->streamDownload(function () use ($rows, $asOf) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ["AR Aging as of {$asOf}"]);
            fputcsv($out, []);
            fputcsv($out, ['Client', 'Invoice Ref', 'Invoice Date', 'Due Date', 'Days Overdue', 'Bucket', 'Balance Due', 'Currency']);
            foreach ($rows as $inv) {
                fputcsv($out, [
                    $inv->client?->name ?? 'N/A',
                    $inv->reference ?? '',
                    $inv->created_at?->format('Y-m-d') ?? '',
                    $inv->due_date?->format('Y-m-d') ?? '',
                    $inv->days_overdue,
                    $inv->bucket,
                    number_format($inv->balance_due, 2, '.', ''),
                    $inv->currency ?? 'USD',
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['', '', '', '', '', 'TOTAL', number_format($rows->sum('balance_due'), 2, '.', ''), '']);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportApAging(Request $request)
    {
        $cid  = $this->cid();
        $asOf = $request->input('as_of', today()->toDateString());
        $asOfDate = \Carbon\Carbon::parse($asOf);

        $supplierRows = DB::table('supplier_ledger_entries')
            ->join('suppliers', 'suppliers.id', '=', 'supplier_ledger_entries.supplier_id')
            ->where('suppliers.company_id', $cid)
            ->whereDate('supplier_ledger_entries.entry_date', '<=', $asOf)
            ->selectRaw("suppliers.name as name, SUM(supplier_ledger_entries.amount) as balance,
                MIN(CASE WHEN supplier_ledger_entries.type='purchase_invoice' THEN supplier_ledger_entries.entry_date ELSE NULL END) as oldest")
            ->groupBy('suppliers.name')
            ->having(DB::raw('SUM(supplier_ledger_entries.amount)'), '>', 0)
            ->get();

        $transporterRows = DB::table('transporter_ledger_entries')
            ->join('transporters', 'transporters.id', '=', 'transporter_ledger_entries.transporter_id')
            ->where('transporters.company_id', $cid)
            ->whereDate('transporter_ledger_entries.entry_date', '<=', $asOf)
            ->selectRaw("transporters.name as name,
                SUM(CASE WHEN transporter_ledger_entries.type IN ('freight_charge','advance') THEN transporter_ledger_entries.amount ELSE -transporter_ledger_entries.amount END) as balance,
                MIN(CASE WHEN transporter_ledger_entries.type='freight_charge' THEN transporter_ledger_entries.entry_date ELSE NULL END) as oldest")
            ->groupBy('transporters.name')
            ->having(DB::raw("SUM(CASE WHEN transporter_ledger_entries.type IN ('freight_charge','advance') THEN transporter_ledger_entries.amount ELSE -transporter_ledger_entries.amount END)"), '>', 0)
            ->get();

        $depotRows = DB::table('depot_ledger_entries')
            ->join('depots', 'depots.id', '=', 'depot_ledger_entries.depot_id')
            ->where('depots.company_id', $cid)->where('depots.is_system', false)
            ->whereDate('depot_ledger_entries.created_at', '<=', $asOf)
            ->selectRaw("depots.name as name,
                SUM(CASE WHEN depot_ledger_entries.type IN ('storage_charge','handling_fee','loading_fee','other_charge') THEN depot_ledger_entries.amount ELSE -depot_ledger_entries.amount END) as balance,
                MIN(CASE WHEN depot_ledger_entries.type IN ('storage_charge','handling_fee','loading_fee','other_charge') THEN depot_ledger_entries.created_at ELSE NULL END) as oldest")
            ->groupBy('depots.name')
            ->having(DB::raw("SUM(CASE WHEN depot_ledger_entries.type IN ('storage_charge','handling_fee','loading_fee','other_charge') THEN depot_ledger_entries.amount ELSE -depot_ledger_entries.amount END)"), '>', 0)
            ->get();

        $bucket = function ($oldest) use ($asOfDate) {
            if (! $oldest) return 'Current';
            $days = (int)\Carbon\Carbon::parse($oldest)->diffInDays($asOfDate);
            return match(true) {
                $days <= 30 => 'Current',
                $days <= 60 => '31-60 days',
                $days <= 90 => '61-90 days',
                default     => '90+ days',
            };
        };

        $filename = "ap-aging-{$asOf}.csv";

        return response()->streamDownload(function () use ($supplierRows, $transporterRows, $depotRows, $bucket) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Payable Type', 'Name', 'Balance Due', 'Age Bucket']);
            foreach ($supplierRows as $r) {
                fputcsv($out, ['Supplier', $r->name, number_format((float)$r->balance,2,'.',''). $bucket($r->oldest)]);
                fputcsv($out, ['Supplier', $r->name, number_format((float)$r->balance, 2, '.', ''), $bucket($r->oldest)]);
            }
            foreach ($transporterRows as $r) {
                fputcsv($out, ['Transporter', $r->name, number_format((float)$r->balance, 2, '.', ''), $bucket($r->oldest)]);
            }
            foreach ($depotRows as $r) {
                fputcsv($out, ['Depot', $r->name, number_format((float)$r->balance, 2, '.', ''), $bucket($r->oldest)]);
            }
            fputcsv($out, []);
            $grand = collect($supplierRows)->sum('balance') + collect($transporterRows)->sum('balance') + collect($depotRows)->sum('balance');
            fputcsv($out, ['', 'TOTAL', number_format((float)$grand, 2, '.', ''), '']);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportThroughput(Request $request)
    {
        $cid    = $this->cid();
        $months = (int)($request->input('months', 6));
        $months = max(3, min($months, 24));
        $from   = now()->startOfMonth()->subMonths($months - 1);

        $grossReceiptsRaw = DB::table('inventory_movements as im')
            ->join('depots as d', 'd.id', '=', 'im.to_depot_id')
            ->where('im.company_id', $cid)->where('im.type', 'receipt')
            ->whereIn('im.ref_type', ['purchase', 'import_truck'])
            ->where('d.is_system', false)
            ->whereDate('im.created_at', '>=', $from)
            ->selectRaw("TO_CHAR(im.created_at,'YYYY-MM') as month, SUM(im.qty) as qty, COUNT(DISTINCT im.ref_id) as count")
            ->groupByRaw("TO_CHAR(im.created_at,'YYYY-MM')")->orderBy('month')->get()->keyBy('month');

        $shrinkageRaw = DB::table('inventory_movements as im')
            ->join('depots as d', 'd.id', '=', 'im.from_depot_id')
            ->where('im.company_id', $cid)->where('im.type', 'adjustment')->where('im.qty', '<', 0)
            ->whereIn('im.ref_type', ['purchase', 'import_truck'])
            ->where('d.is_system', false)
            ->whereDate('im.created_at', '>=', $from)
            ->selectRaw("TO_CHAR(im.created_at,'YYYY-MM') as month, SUM(ABS(im.qty)) as qty")
            ->groupByRaw("TO_CHAR(im.created_at,'YYYY-MM')")->orderBy('month')->get()->keyBy('month');

        $salesRaw = DB::table('inventory_movements')
            ->where('company_id', $cid)->where('type', 'issue')->where('ref_type', 'sale')
            ->whereDate('created_at', '>=', $from)
            ->selectRaw("TO_CHAR(created_at,'YYYY-MM') as month, SUM(qty) as qty, COUNT(DISTINCT ref_id) as count")
            ->groupByRaw("TO_CHAR(created_at,'YYYY-MM')")->orderBy('month')->get()->keyBy('month');

        $revenueRaw = DB::table('sales')
            ->where('company_id', $cid)->where('status', 'posted')->whereDate('created_at', '>=', $from)
            ->selectRaw("TO_CHAR(created_at,'YYYY-MM') as month, SUM(total) as revenue")
            ->groupByRaw("TO_CHAR(created_at,'YYYY-MM')")->orderBy('month')->get()->keyBy('month');

        $series = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key       = now()->startOfMonth()->subMonths($i)->format('Y-m');
            $label     = now()->startOfMonth()->subMonths($i)->format('M Y');
            $grossQty  = (float)($grossReceiptsRaw[$key]->qty ?? 0);
            $shrinkage = (float)($shrinkageRaw[$key]->qty ?? 0);
            $series[] = [
                'month'         => $label,
                'received_qty'  => round($grossQty - $shrinkage),
                'shrinkage_qty' => round($shrinkage),
                'sold_qty'      => round((float)($salesRaw[$key]->qty ?? 0)),
                'revenue'       => round((float)($revenueRaw[$key]->revenue ?? 0), 2),
            ];
        }

        $filename = "throughput-last-{$months}-months.csv";

        return response()->streamDownload(function () use ($series) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Month', 'Received net of shrinkage (L)', 'Shrinkage (L)', 'Sold (L)', 'Revenue']);
            foreach ($series as $row) {
                fputcsv($out, [
                    $row['month'],
                    number_format($row['received_qty'], 0, '.', ''),
                    number_format($row['shrinkage_qty'], 0, '.', ''),
                    number_format($row['sold_qty'], 0, '.', ''),
                    number_format($row['revenue'], 2, '.', ''),
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['TOTALS',
                number_format(array_sum(array_column($series,'received_qty')), 0, '.', ''),
                number_format(array_sum(array_column($series,'shrinkage_qty')), 0, '.', ''),
                number_format(array_sum(array_column($series,'sold_qty')), 0, '.', ''),
                number_format(array_sum(array_column($series,'revenue')), 2, '.', ''),
            ]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
