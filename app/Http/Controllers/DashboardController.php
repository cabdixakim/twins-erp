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

        // Balance per transporter × currency (only positive balances)
        $entries = TransporterLedgerEntry::where('company_id', $cid)
            ->selectRaw('transporter_id, currency, SUM(amount) as balance')
            ->groupBy('transporter_id', 'currency')
            ->havingRaw('SUM(amount) > 0')
            ->get();

        // Total per currency, sorted descending
        $byCurrency = $entries
            ->groupBy('currency')
            ->map(fn($rows) => $rows->sum('balance'))
            ->sortDesc();

        // Top 3 transporter+currency combos by balance
        $topTransporters = collect();
        if ($entries->isNotEmpty()) {
            $topEntries = $entries->sortByDesc('balance')->take(3);
            $transporterIds = $topEntries->pluck('transporter_id')->unique();
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

        // Open purchases: draft + confirmed
        $openPurchasesCount = Purchase::where('company_id', $cid)
            ->whereIn('status', ['draft', 'confirmed', 'nominated'])
            ->count();

        $openByStatus = Purchase::where('company_id', $cid)
            ->whereIn('status', ['draft', 'confirmed', 'nominated'])
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        // Stock on hand by depot (exclude system depots like CROSS DOCK)
        $depotStockRows = DepotStock::where('depot_stocks.company_id', $cid)
            ->join('depots', 'depots.id', '=', 'depot_stocks.depot_id')
            ->where('depots.is_system', false)
            ->selectRaw('depots.id as depot_id, depots.name as depot_name, SUM(depot_stocks.qty_on_hand) as total_qty')
            ->groupBy('depots.id', 'depots.name')
            ->orderBy('depots.name')
            ->get();

        $totalStockOnHand = $depotStockRows->sum('total_qty');

        // Supplier payables (only positive = we owe them)
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

        // Top 3 supplier balances
        $topSuppliers = collect();
        if ($supplierBalances->isNotEmpty()) {
            $top = $supplierBalances->sortByDesc('balance')->take(3);
            $sIds = $top->pluck('supplier_id')->unique();
            $sNames = DB::table('suppliers')->where('company_id', $cid)->whereIn('id', $sIds)->pluck('name', 'id');
            $topSuppliers = $top->map(fn($r) => (object)[
                'id' => $r->supplier_id, 'name' => $sNames[$r->supplier_id] ?? 'Unknown',
                'balance' => $r->balance, 'currency' => $r->currency,
            ])->values();
        }

        // Depot payables (only positive = we owe depots)
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
            $top = $depotBalances->sortByDesc('balance')->take(3);
            $dIds = $top->pluck('depot_id')->unique();
            $dNames = Depot::where('company_id', $cid)->whereIn('id', $dIds)->pluck('name', 'id');
            $topDepots = $top->map(fn($r) => (object)[
                'id' => $r->depot_id, 'name' => $dNames[$r->depot_id] ?? 'Unknown',
                'balance' => $r->balance, 'currency' => $r->currency,
            ])->values();
        }

        // Client AR receivables (positive = client owes us)
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
            $top = $clientARBalances->sortByDesc('balance')->take(3);
            $cIds = $top->pluck('client_id')->unique();
            $cNames = Client::where('company_id', $cid)->whereIn('id', $cIds)->pluck('name', 'id');
            $topARClients = $top->map(fn($r) => (object)[
                'id' => $r->client_id, 'name' => $cNames[$r->client_id] ?? 'Unknown',
                'balance' => $r->balance, 'currency' => $r->currency,
            ])->values();
        }

        return view('dashboard.index', compact(
            'byCurrency',
            'topTransporters',
            'openPurchasesCount',
            'openByStatus',
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
            'totalAR'
        ));
    }
}
