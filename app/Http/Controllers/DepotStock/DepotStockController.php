<?php

namespace App\Http\Controllers\DepotStock;

use App\Http\Controllers\Controller;
use App\Models\Depot;
use App\Models\DepotStock;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Http\Request;

class DepotStockController extends Controller
{
    public function index(Request $request)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $depots = Depot::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->where('is_system', false)
            ->orderBy('name')
            ->get();

        $selectedDepotId = (int) $request->query('depot', 0);
        if ($selectedDepotId <= 0 && $depots->count() > 0) {
            $selectedDepotId = (int) $depots->first()->id;
        }

        $currentDepot = $selectedDepotId ? $depots->firstWhere('id', $selectedDepotId) : null;

        $movements = collect();
        $balance   = collect();
        $products  = collect();
        $stats     = ['total_in' => 0, 'total_out' => 0, 'net' => 0, 'count' => 0];

        if ($currentDepot) {
            $products = Product::where('company_id', $cid)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);

            // All-time summary (ignores filters — always the full picture)
            $stats['total_in']  = (float) InventoryMovement::where('company_id', $cid)
                ->where('to_depot_id', $currentDepot->id)
                ->sum('qty');
            $stats['total_out'] = (float) InventoryMovement::where('company_id', $cid)
                ->where('from_depot_id', $currentDepot->id)
                ->sum('qty');
            $stats['net'] = $stats['total_in'] - $stats['total_out'];

            // Current balance from depot_stocks, aggregated per product (no batch breakdown)
            $balance = DepotStock::where('company_id', $cid)
                ->where('depot_id', $currentDepot->id)
                ->selectRaw('product_id, SUM(qty_on_hand) as total_on_hand, SUM(qty_reserved) as total_reserved')
                ->groupBy('product_id')
                ->with('product:id,name')
                ->get();

            // Movements query with optional filters
            $q = InventoryMovement::where('company_id', $cid)
                ->where(function ($sub) use ($currentDepot) {
                    $sub->where('to_depot_id', $currentDepot->id)
                        ->orWhere('from_depot_id', $currentDepot->id);
                })
                ->with(['product:id,name'])
                ->latest('id');

            if ($request->filled('type'))    $q->where('type', $request->type);
            if ($request->filled('product')) $q->where('product_id', $request->product);
            if ($request->filled('from'))    $q->whereDate('created_at', '>=', $request->from);
            if ($request->filled('to'))      $q->whereDate('created_at', '<=', $request->to);
            if ($request->filled('search')) {
                $term = '%' . $request->search . '%';
                $q->where(function ($s) use ($term) {
                    $s->where('reference', 'like', $term)
                      ->orWhere('notes', 'like', $term);
                });
            }

            $stats['count'] = $q->count();
            $movements = $q->paginate(50)->withQueryString();
        }

        return view('depot-stock.index', compact(
            'depots', 'currentDepot', 'movements', 'balance', 'stats', 'products'
        ));
    }

    public function exportCsv(Request $request)
    {
        $u       = auth()->user();
        $cid     = (int) ($u?->active_company_id ?? 0);
        $depotId = (int) $request->query('depot', 0);

        $q = InventoryMovement::where('company_id', $cid)
            ->with(['product:id,name', 'toDepot:id,name', 'fromDepot:id,name'])
            ->latest('id');

        if ($depotId > 0) {
            $q->where(function ($sub) use ($depotId) {
                $sub->where('to_depot_id', $depotId)
                    ->orWhere('from_depot_id', $depotId);
            });
        }

        if ($request->filled('type'))    $q->where('type', $request->type);
        if ($request->filled('product')) $q->where('product_id', $request->product);
        if ($request->filled('from'))    $q->whereDate('created_at', '>=', $request->from);
        if ($request->filled('to'))      $q->whereDate('created_at', '<=', $request->to);

        $depotName = $depotId ? (Depot::find($depotId)?->name ?? 'depot') : 'all-depots';
        $filename  = 'movements-' . str_replace(' ', '-', strtolower($depotName)) . '-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Direction', 'Type', 'Product', 'Depot In', 'Depot Out', 'Qty (L)', 'Unit Cost', 'Total Cost', 'Reference', 'Notes']);
            $q->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $m) {
                    $direction = match(true) {
                        $m->type === 'adjustment'     => 'ADJ',
                        $m->to_depot_id !== null      => 'IN',
                        $m->from_depot_id !== null    => 'OUT',
                        default                       => '—',
                    };
                    fputcsv($out, [
                        $m->created_at?->format('Y-m-d H:i') ?? '',
                        $direction,
                        strtoupper($m->type),
                        $m->product?->name ?? '',
                        $m->toDepot?->name ?? '',
                        $m->fromDepot?->name ?? '',
                        number_format((float) $m->qty, 3, '.', ''),
                        number_format((float) $m->unit_cost, 6, '.', ''),
                        number_format((float) $m->total_cost, 2, '.', ''),
                        $m->reference ?? '',
                        $m->notes ?? '',
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
