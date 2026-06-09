<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\Batch;
use App\Models\BatchCost;
use App\Models\Sale;
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

    /** P&L / Margin by batch */
    public function plByBatch(Request $request)
    {
        $cid    = $this->cid();
        $from   = $request->input('from');
        $to     = $request->input('to');
        $search = $request->input('search');

        $batchQ = Batch::where('company_id', $cid)
            ->with(['product'])
            ->withSum('batchCosts as landed_cost_total', 'amount')
            ->orderByDesc('purchased_at');

        if ($from)   $batchQ->whereDate('purchased_at', '>=', $from);
        if ($to)     $batchQ->whereDate('purchased_at', '<=', $to);
        if ($search) $batchQ->where('code', 'like', "%{$search}%");

        $batches = $batchQ->paginate(30)->withQueryString();

        // For each batch, calculate revenue from sales via consumptions
        $batchIds = $batches->pluck('id')->all();

        // Sales revenue per batch via inventory_consumptions
        $revenueByBatch = DB::table('inventory_consumptions')
            ->join('sales', function ($j) {
                $j->on('sales.id', '=', 'inventory_consumptions.ref_id')
                  ->where('inventory_consumptions.ref_type', 'sale');
            })
            ->whereIn('inventory_consumptions.batch_id', $batchIds)
            ->where('inventory_consumptions.company_id', $cid)
            ->selectRaw('inventory_consumptions.batch_id, SUM(inventory_consumptions.qty * sales.unit_price) as revenue, SUM(inventory_consumptions.qty) as qty_sold')
            ->groupBy('inventory_consumptions.batch_id')
            ->get()
            ->keyBy('batch_id');

        // Enrich batches with P&L data
        $batches->getCollection()->transform(function ($batch) use ($revenueByBatch) {
            $rev      = $revenueByBatch[$batch->id] ?? null;
            $revenue  = round((float)($rev?->revenue ?? 0), 2);
            $qtySold  = round((float)($rev?->qty_sold ?? 0), 2);
            $purchase = round((float)$batch->total_cost, 2);
            $landed   = round((float)($batch->landed_cost_total ?? 0), 2);

            // COGS = proportional purchase cost for qty sold
            $cogs     = $batch->qty_purchased > 0
                ? round($purchase * ($qtySold / $batch->qty_purchased), 2)
                : 0;

            $totalCost   = $cogs + $landed;
            $grossMargin = round($revenue - $totalCost, 2);
            $marginPct   = $revenue > 0 ? round($grossMargin / $revenue * 100, 1) : null;

            $batch->_revenue      = $revenue;
            $batch->_qty_sold     = $qtySold;
            $batch->_purchase     = $purchase;
            $batch->_landed       = $landed;
            $batch->_cogs         = $cogs;
            $batch->_total_cost   = $totalCost;
            $batch->_gross_margin = $grossMargin;
            $batch->_margin_pct   = $marginPct;

            return $batch;
        });

        // Summary totals
        $totals = [
            'revenue'      => $batches->sum('_revenue'),
            'cogs'         => $batches->sum('_cogs'),
            'landed'       => $batches->sum('_landed'),
            'gross_margin' => $batches->sum('_gross_margin'),
        ];
        if ($totals['revenue'] > 0) {
            $totals['margin_pct'] = round($totals['gross_margin'] / $totals['revenue'] * 100, 1);
        }

        return view('reports.pl', compact('batches', 'totals'));
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

    /** Volume throughput — purchases received vs. sales posted by month */
    public function throughput(Request $request)
    {
        $cid    = $this->cid();
        $months = (int)($request->input('months', 6));
        $months = max(3, min($months, 24));

        $from = now()->startOfMonth()->subMonths($months - 1);

        // Monthly purchases received (qty)
        $purchasedRaw = DB::table('inventory_movements')
            ->where('company_id', $cid)
            ->where('type', 'receipt')
            ->where('ref_type', 'purchase')
            ->whereDate('created_at', '>=', $from)
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month, SUM(qty) as qty, COUNT(DISTINCT ref_id) as count")
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM')")
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
            $key    = now()->startOfMonth()->subMonths($i)->format('Y-m');
            $label  = now()->startOfMonth()->subMonths($i)->format('M Y');
            $series[] = [
                'month'            => $key,
                'label'            => $label,
                'purchased_qty'    => round((float)($purchasedRaw[$key]->qty ?? 0), 0),
                'purchased_count'  => (int)($purchasedRaw[$key]->count ?? 0),
                'sold_qty'         => round((float)($salesRaw[$key]->qty ?? 0), 0),
                'sold_count'       => (int)($salesRaw[$key]->count ?? 0),
                'revenue'          => round((float)($revenueRaw[$key]->revenue ?? 0), 2),
            ];
        }

        // Summary totals
        $totals = [
            'purchased_qty'   => array_sum(array_column($series, 'purchased_qty')),
            'sold_qty'        => array_sum(array_column($series, 'sold_qty')),
            'revenue'         => array_sum(array_column($series, 'revenue')),
            'purchased_count' => array_sum(array_column($series, 'purchased_count')),
            'sold_count'      => array_sum(array_column($series, 'sold_count')),
        ];

        return view('reports.throughput', compact('series', 'totals', 'months'));
    }
}
