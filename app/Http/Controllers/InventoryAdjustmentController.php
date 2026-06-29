<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Services\InventoryLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryAdjustmentController extends Controller
{
    private function authorise(): int
    {
        $user = auth()->user();
        abort_if(!$user, 401);
        $cid  = (int) $user->active_company_id;
        abort_if(!$cid, 403);
        return $cid;
    }

    public function index(Request $request)
    {
        $cid = $this->authorise();

        $query = InventoryAdjustment::with(['product', 'depot', 'batch'])
            ->where('company_id', $cid)
            ->orderByDesc('id');

        if ($request->filled('depot')) {
            $query->where('depot_id', (int) $request->depot);
        }
        if ($request->filled('reason')) {
            $query->where('reason_type', $request->reason);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $adjustments = $query->paginate(40)->withQueryString();
        $depots      = Depot::where('company_id', $cid)->where('is_active', true)
                            ->where(fn($q) => $q->whereNull('is_system')->orWhere('is_system', 0))
                            ->orderBy('name')->get();

        $totalValue = InventoryAdjustment::where('company_id', $cid)->sum('total_value');
        $currency   = DB::table('companies')->where('id', $cid)->value('base_currency') ?? '';

        return view('inventory-adjustments.index', compact('adjustments', 'depots', 'totalValue', 'currency'));
    }

    public function create(Request $request)
    {
        $cid = $this->authorise();

        $depots   = Depot::where('company_id', $cid)->where('is_active', true)
                         ->where(fn($q) => $q->whereNull('is_system')->orWhere('is_system', 0))
                         ->orderBy('name')->get();
        $products = Product::where('company_id', $cid)->where('is_active', true)->orderBy('name')->get();

        $selectedDepotId = $request->integer('depot_id') ?: null;
        $selectedProductId = $request->integer('product_id') ?: null;

        $stockInfo = null;
        if ($selectedDepotId && $selectedProductId) {
            $stockInfo = DB::table('depot_stocks')
                ->where('company_id', $cid)
                ->where('depot_id', $selectedDepotId)
                ->where('product_id', $selectedProductId)
                ->selectRaw('SUM(qty_on_hand) as total_qty, AVG(unit_cost) as avg_cost')
                ->first();
        }

        return view('inventory-adjustments.create', compact(
            'depots', 'products', 'selectedDepotId', 'selectedProductId', 'stockInfo'
        ));
    }

    public function store(Request $request, InventoryLedger $ledger)
    {
        $cid = $this->authorise();
        $u   = auth()->user();

        $data = $request->validate([
            'depot_id'    => 'required|integer',
            'product_id'  => 'required|integer',
            'reason_type' => 'required|in:write_off,meter_variance,stock_count_correction,transit_loss',
            'qty'         => 'required|numeric|min:0.001',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $depotOk = Depot::where('company_id', $cid)->whereKey((int) $data['depot_id'])->where('is_active', true)->exists();
        if (!$depotOk) {
            return back()->withErrors(['depot_id' => 'Invalid depot.'])->withInput();
        }
        $productOk = Product::where('company_id', $cid)->whereKey((int) $data['product_id'])->where('is_active', true)->exists();
        if (!$productOk) {
            return back()->withErrors(['product_id' => 'Invalid product.'])->withInput();
        }

        $depotId   = (int) $data['depot_id'];
        $productId = (int) $data['product_id'];
        $qty       = (float) $data['qty'];

        $currentStock = DB::table('depot_stocks')
            ->where('company_id', $cid)
            ->where('depot_id', $depotId)
            ->where('product_id', $productId)
            ->sum('qty_on_hand');

        if ($currentStock + 1e-6 < $qty) {
            return back()->withErrors(['qty' => "Cannot write off {$qty} — only " . number_format($currentStock, 3) . " on hand."])->withInput();
        }

        try {
            $ledger->adjustment([
                'company_id'  => $cid,
                'product_id'  => $productId,
                'depot_id'    => $depotId,
                'reason_type' => $data['reason_type'],
                'qty'         => $qty,
                'notes'       => $data['notes'] ?? null,
                'ref_type'    => 'manual',
                'ref_id'      => null,
                'created_by'  => $u?->id,
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['qty' => $e->getMessage()])->withInput();
        }

        return redirect()->route('inventory-adjustments.index')
            ->with('status', 'Stock adjustment of ' . number_format($qty, 3) . ' units posted.');
    }
}
