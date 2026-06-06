<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\TransporterLedgerEntry;
use App\Models\Transporter;
use App\Models\Purchase;
use App\Models\DepotStock;
use App\Models\Depot;

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

        return view('dashboard.index', compact(
            'byCurrency',
            'topTransporters',
            'openPurchasesCount',
            'openByStatus',
            'depotStockRows',
            'totalStockOnHand'
        ));
    }
}
