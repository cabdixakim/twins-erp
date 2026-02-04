<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function index()
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $purchases = Purchase::query()
            ->where('company_id', $cid)
            ->latest('id')
            ->paginate(20);

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $suppliers = Supplier::query()
            ->where('company_id', $cid)
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('purchases.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $data = $request->validate([
            'type'          => 'required|in:import,local_depot',
            'supplier_id'   => 'nullable|integer',
            'product_id'    => 'required|integer',
            'purchase_date' => 'nullable|date',
            'qty'           => 'required|numeric|min:0.001',
            'unit_price'    => 'required|numeric|min:0',
            'currency'      => 'required|string|max:8',
            'notes'         => 'nullable|string',
        ]);

        // supplier must belong to active company if provided
        if (!empty($data['supplier_id'])) {
            $ok = Supplier::query()
                ->where('company_id', $cid)
                ->whereKey((int) $data['supplier_id'])
                ->exists();

            if (!$ok) {
                return back()->withErrors(['supplier_id' => 'Invalid supplier for this company.'])->withInput();
            }
        }

        // product must belong to active company
        $productOk = Product::query()
            ->where('company_id', $cid)
            ->whereKey((int) $data['product_id'])
            ->exists();

        if (!$productOk) {
            return back()->withErrors(['product_id' => 'Invalid product for this company.'])->withInput();
        }

        $purchase = Purchase::create([
            'company_id'    => $cid,
            'type'          => $data['type'],
            'supplier_id'   => $data['supplier_id'] ?? null,
            'product_id'    => (int) $data['product_id'],
            'purchase_date' => $data['purchase_date'] ?? null,
            'qty'           => $data['qty'],
            'unit_price'    => $data['unit_price'],
            'currency'      => $data['currency'],
            'status'        => 'draft',
            'notes'         => $data['notes'] ?? null,
            'created_by'    => $u?->id,
            'updated_by'    => $u?->id,
        ]);

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Purchase created (draft).');
    }

    public function show(Purchase $purchase)
    {
        // Route binding is already company-scoped by your trait
        return view('purchases.show', compact('purchase'));
    }

    public function confirm(Purchase $purchase)
    {
        $u = auth()->user();

        if ($purchase->status !== 'draft') {
            return back()->with('error', 'Only draft purchases can be confirmed.');
        }

        DB::transaction(function () use ($purchase, $u) {
            if (!$purchase->batch_id) {
                // Friendly batch code (unique enough; you can later make per-company counter)
                $code = 'BATCH-' . now()->format('Y') . '-' . strtoupper(Str::random(6));

                $qty = (float) $purchase->qty;
                $unit = (float) $purchase->unit_price;

                $batch = Batch::create([
                    'company_id'     => $purchase->company_id,
                    'product_id'     => $purchase->product_id,
                    'source_type'    => $purchase->type === 'local_depot' ? 'local_depot' : 'import',
                    'source_ref'     => 'purchase:' . $purchase->id,

                    'code'           => $code,
                    'name'           => null,
                    'supplier_id'    => $purchase->supplier_id,

                    'qty_purchased'  => $qty,
                    'qty_received'   => 0,
                    'qty_remaining'  => $qty,

                    'total_cost'     => round($qty * $unit, 2),
                    'unit_cost'      => $qty > 0 ? $unit : 0,

                    'status'         => 'active',
                    'purchased_at'   => $purchase->purchase_date ? $purchase->purchase_date->startOfDay() : now(),

                    'created_by'     => $u?->id,
                    'updated_by'     => $u?->id,
                ]);

                $purchase->batch_id = $batch->id;
            }

            $purchase->status     = 'confirmed';
            $purchase->updated_by = $u?->id;
            $purchase->save();
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('status', "Purchase confirmed. Batch created.");
    }
}