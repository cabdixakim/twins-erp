<?php

namespace App\Http\Controllers\DepotStock;

use App\Http\Controllers\Controller;
use App\Models\Depot;
use App\Models\DepotStock;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;

class DepotStockController extends Controller
{
    public function index(Request $request)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        // Depots list (exclude system depots if you want only physical – but CROSS DOCK is useful here)
        $depots = Depot::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedDepotId = (int) $request->query('depot', 0);

        // If no depot selected, default to first depot
        if ($selectedDepotId <= 0 && $depots->count() > 0) {
            $selectedDepotId = (int) $depots->first()->id;
        }

        $currentDepot = $selectedDepotId
            ? $depots->firstWhere('id', $selectedDepotId)
            : null;

        $stocks = collect();
        $metrics = [
            'on_hand_l'   => 0,
            'reserved_l'  => 0,
            'value'       => 0,
            'batches'     => 0,
        ];

        $recentMovements = collect();

        if ($currentDepot) {
            $stocks = DepotStock::query()
                ->where('company_id', $cid)
                ->where('depot_id', $currentDepot->id)
                ->with([
                    'product:id,name',
                    'batch:id,code,unit_cost,purchased_at',
                ])
                ->orderByDesc('qty_on_hand')
                ->get();

            $metrics['on_hand_l']  = (float) $stocks->sum('qty_on_hand');
            $metrics['reserved_l'] = (float) $stocks->sum('qty_reserved');
            $metrics['value']      = (float) $stocks->sum(fn ($r) => ((float)$r->qty_on_hand) * ((float)$r->unit_cost));
            $metrics['batches']    = (int) $stocks->count();

            $recentMovements = InventoryMovement::query()
                ->where('company_id', $cid)
                ->where('to_depot_id', $currentDepot->id)
                ->with(['product:id,name', 'batch:id,code'])
                ->latest('id')
                ->limit(12)
                ->get();
        }

        return view('depot-stock.index', compact(
            'depots',
            'currentDepot',
            'stocks',
            'metrics',
            'recentMovements'
        ));
    }
}