<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ImportTruck;

class ClearanceController extends Controller
{
    public function index(Request $request)
    {
        $cid    = auth()->user()->active_company_id;
        $status = $request->query('status', 'all');
        $search = trim($request->query('search', ''));

        $statuses = ['all', 'nominated', 'loaded', 'in_transit', 'border_cleared', 'delivered', 'loading_failed'];
        if (! in_array($status, $statuses)) {
            $status = 'all';
        }

        $base = ImportTruck::query()
            ->whereHas('nomination', fn($q) => $q->whereHas('purchase', fn($q2) => $q2->where('company_id', $cid)))
            ->with([
                'nomination.purchase.product',
                'nomination.purchase.supplier',
                'nomination.transporter',
                'depot',
            ]);

        if ($status !== 'all') {
            $base->where('status', $status);
        }

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('truck_reg',   'ilike', "%{$search}%")
                  ->orWhere('trailer_reg', 'ilike', "%{$search}%")
                  ->orWhere('tr8_number', 'ilike', "%{$search}%")
                  ->orWhere('t1_number',  'ilike', "%{$search}%")
                  ->orWhereHas('nomination.purchase', fn($q2) => $q2->where('reference', 'ilike', "%{$search}%"));
            });
        }

        $trucks = $base->orderByRaw("CASE status
                WHEN 'in_transit'     THEN 1
                WHEN 'border_cleared' THEN 2
                WHEN 'loaded'         THEN 3
                WHEN 'nominated'      THEN 4
                WHEN 'delivered'      THEN 5
                WHEN 'loading_failed' THEN 6
                ELSE 7 END")
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $counts = ImportTruck::query()
            ->whereHas('nomination', fn($q) => $q->whereHas('purchase', fn($q2) => $q2->where('company_id', $cid)))
            ->selectRaw("status, count(*) as cnt")
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $totalCount = array_sum($counts);

        return view('clearances.index', compact('trucks', 'status', 'search', 'counts', 'totalCount'));
    }
}
