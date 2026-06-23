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
        $duty   = $request->query('duty', 'all');   // 'all' | 'pending' | 'posted' | 'waived' | 'na'
        $search = trim($request->query('search', ''));

        $statuses = ['all', 'nominated', 'loaded', 'in_transit', 'border_cleared', 'delivered', 'loading_failed'];
        if (! in_array($status, $statuses)) {
            $status = 'all';
        }
        $dutyFilters = ['all', 'pending', 'posted', 'waived', 'na'];
        if (! in_array($duty, $dutyFilters)) {
            $duty = 'all';
        }

        // Base scope: all trucks for this company
        $companyScope = ImportTruck::query()
            ->whereHas('nomination', fn($q) => $q->whereHas('purchase', fn($q2) => $q2->where('company_id', $cid)));

        // -- KPI aggregates (always unfiltered) --
        $counts = (clone $companyScope)
            ->selectRaw("status, count(*) as cnt")
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $totalCount      = array_sum($counts);
        $atBorderCount   = $counts['border_cleared'] ?? 0;
        $inTransitCount  = $counts['in_transit'] ?? 0;

        $qtyInTransit = (clone $companyScope)
            ->whereIn('status', ['loaded', 'in_transit'])
            ->sum('qty_loaded');

        $docsMissingCount = (clone $companyScope)
            ->whereIn('status', ['border_cleared', 'delivered'])
            ->where(fn($q) => $q->whereNull('tr8_number')->orWhereNull('t1_number'))
            ->count();

        $dutyPendingCount = (clone $companyScope)
            ->where(fn($q) => $q
                ->where(fn($q2) => $q2->where('duty_amount', '>', 0)
                    ->where(fn($q3) => $q3->whereNull('duty_status')->orWhere('duty_status', '!=', 'posted')))
                ->orWhere(fn($q2) => $q2->where('duty_rate_per_1000l', '>', 0)->whereNull('duty_amount'))
            )
            ->count();

        // -- Filtered query for the table --
        $base = (clone $companyScope)->with([
            'nomination.purchase.product',
            'nomination.purchase.supplier',
            'nomination.transporter',
            'depot',
        ]);

        if ($status !== 'all') {
            $base->where('status', $status);
        }

        // Duty filter
        if ($duty === 'pending') {
            $base->where(fn($q) => $q
                ->where(fn($q2) => $q2->where('duty_amount', '>', 0)
                    ->where(fn($q3) => $q3->whereNull('duty_status')->orWhere('duty_status', '!=', 'posted')))
                ->orWhere(fn($q2) => $q2->where('duty_rate_per_1000l', '>', 0)->whereNull('duty_amount'))
            );
        } elseif ($duty === 'posted') {
            $base->where('duty_status', 'posted');
        } elseif ($duty === 'waived') {
            $base->where('duty_amount', 0)->where('duty_rate_per_1000l', 0);
        } elseif ($duty === 'na') {
            $base->whereNull('duty_rate_per_1000l')->whereNull('duty_amount');
        }

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('truck_reg',    'ilike', "%{$search}%")
                  ->orWhere('trailer_reg', 'ilike', "%{$search}%")
                  ->orWhere('tr8_number',  'ilike', "%{$search}%")
                  ->orWhere('t1_number',   'ilike', "%{$search}%")
                  ->orWhere('border_post', 'ilike', "%{$search}%")
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

        return view('clearances.index', compact(
            'trucks', 'status', 'duty', 'search', 'counts', 'totalCount',
            'atBorderCount', 'inTransitCount', 'qtyInTransit',
            'docsMissingCount', 'dutyPendingCount'
        ));
    }
}
