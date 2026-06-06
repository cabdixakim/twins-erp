<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\TransporterLedgerEntry;
use App\Models\Transporter;

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

        return view('dashboard.index', compact('byCurrency', 'topTransporters'));
    }
}
