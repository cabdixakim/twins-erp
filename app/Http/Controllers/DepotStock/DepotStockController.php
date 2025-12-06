<?php

namespace App\Http\Controllers\DepotStock;

use App\Http\Controllers\Controller;
use App\Models\Depot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepotStockController extends Controller
{
    /**
     * Main Depot Stock dashboard.
     *
     * Shows:
     *  - Left: list of depots
     *  - Right: selected depot summary + action shortcuts
     */
    public function index(Request $request): View
    {
        $depots = Depot::orderBy('name')->get();

        $currentDepot = null;

        if ($depots->count() > 0) {
            // default to first depot
            $currentDepot = $depots->first();

            // if a specific depot is requested, and exists, use it instead
            if ($request->filled('depot')) {
                $selected = $depots->firstWhere('id', (int) $request->input('depot'));
                if ($selected) {
                    $currentDepot = $selected;
                }
            }
        }

        // For now, we don’t have real stock movements yet.
        // We'll plug real numbers here once the stock tables exist.
        $metrics = [
            'on_hand_l'     => 0.0,
            'in_transit_l'  => 0.0,
            'reserved_l'    => 0.0,
            'last_activity' => null,
        ];

        return view('depot-stock.index', [
            'depots'       => $depots,
            'currentDepot' => $currentDepot,
            'metrics'      => $metrics,
        ]);
    }
}