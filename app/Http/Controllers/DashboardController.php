<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\TransporterLedgerEntry;
use App\Models\Transporter;

class DashboardController extends Controller {
    public function index() {
        $cid = (int) auth()->user()->active_company_id;

        $balances = TransporterLedgerEntry::where('company_id', $cid)
            ->selectRaw('transporter_id, SUM(amount) as balance')
            ->groupBy('transporter_id')
            ->having('balance', '>', 0)
            ->pluck('balance', 'transporter_id');

        $totalFreightPayable = $balances->sum();

        $topTransporters = collect();
        if ($balances->isNotEmpty()) {
            $topIds = $balances->sortDesc()->take(3)->keys();
            $transporterNames = Transporter::where('company_id', $cid)
                ->whereIn('id', $topIds)
                ->pluck('name', 'id');

            $topTransporters = $topIds->map(fn($id) => (object)[
                'id'      => $id,
                'name'    => $transporterNames[$id] ?? 'Unknown',
                'balance' => $balances[$id],
            ]);
        }

        return view('dashboard.index', compact('totalFreightPayable', 'topTransporters'));
    }
}
