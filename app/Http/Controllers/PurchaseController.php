<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Depot;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        // --- Filters (GET params) ---
        $q        = trim((string) $request->query('q', ''));
        $supplier = trim((string) $request->query('supplier', '')); // can be supplier id OR name (we'll accept both)
        $type     = trim((string) $request->query('type', ''));
        $status   = trim((string) $request->query('status', ''));

        // Supplier dropdown options (names)
        $supplierOptions = Supplier::query()
            ->where('company_id', $cid)
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        $purchasesQuery = Purchase::query()
            ->where('company_id', $cid)
            ->with(['creator', 'supplier']); // IMPORTANT: supplier for Supplier column + filtering

        // Search (Purchase #, Batch #, Supplier name)
        if ($q !== '') {
            $purchasesQuery->where(function ($qq) use ($q) {
                // If numeric, allow direct id match
                if (ctype_digit($q)) {
                    $qq->orWhere('id', (int) $q)
                       ->orWhere('batch_id', (int) $q);
                }

                $qq->orWhere('id', 'like', '%' . $q . '%')
                   ->orWhere('batch_id', 'like', '%' . $q . '%')
                   ->orWhereHas('supplier', function ($s) use ($q) {
                       $s->where('name', 'like', '%' . $q . '%');
                   });
            });
        }

        // Supplier filter: accept supplier name OR supplier id
        if ($supplier !== '') {
            $purchasesQuery->where(function ($qq) use ($supplier) {
                if (ctype_digit($supplier)) {
                    $qq->where('supplier_id', (int) $supplier);
                } else {
                    $qq->whereHas('supplier', function ($s) use ($supplier) {
                        $s->where('name', $supplier);
                    });
                }
            });
        }

        // Type filter
        if ($type !== '' && in_array($type, ['import', 'local_depot', 'cross_dock'], true)) {
            $purchasesQuery->where('type', $type);
        }

        // Status filter
        if ($status !== '' && in_array($status, ['draft', 'confirmed'], true)) {
            $purchasesQuery->where('status', $status);
        }

        $purchases = $purchasesQuery
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('purchases.index', compact('purchases', 'supplierOptions'));
    }

    public function create()
    {
        $u = auth()->user();
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

        // Only physical depots for local_depot selection (exclude system depots like CROSS DOCK)
        $depots = DB::table('depots')
            ->where('company_id', $cid)
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('is_system')->orWhere('is_system', 0);
            })
            ->orderBy('name')
            ->get();

        return view('purchases.create', compact('suppliers', 'products', 'depots'));
    }

    public function store(Request $request)
    {
        $u = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $data = $request->validate([
            'type' => 'required|in:import,local_depot,cross_dock',
            'supplier_id' => 'nullable|integer',
            'product_id' => 'required|integer',
            'depot_id' => 'nullable|integer', // required only for local_depot (validated below)
            'purchase_date' => 'nullable|date',
            'qty' => 'required|numeric|min:0.001',
            'unit_price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:8',
            'notes' => 'nullable|string',
            'reference' => 'nullable|string|max:64',
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

        // depot rules
        if (($data['type'] ?? null) === 'local_depot') {
            if (empty($data['depot_id'])) {
                return back()->withErrors(['depot_id' => 'Depot is required for local depot purchases.'])->withInput();
            }

            $depotOk = DB::table('depots')
                ->where('company_id', $cid)
                ->where('id', (int) $data['depot_id'])
                ->where('is_active', 1)
                ->where(function ($q) {
                    $q->whereNull('is_system')->orWhere('is_system', 0);
                })
                ->exists();

            if (!$depotOk) {
                return back()->withErrors(['depot_id' => 'Invalid depot for this company.'])->withInput();
            }
        } else {
            // import + cross_dock should not store a physical depot id
            $data['depot_id'] = null;
        }

        $purchase = DB::transaction(function () use ($cid, $u, $data) {

            // Company-scoped sequence (locks rows for this company)
            $nextSeq = (int) Purchase::query()
                ->where('company_id', $cid)
                ->lockForUpdate()
                ->max('sequence_no');

            $nextSeq = $nextSeq + 1;

            $purchaseDate = $data['purchase_date'] ?? Carbon::today();
            $year = Carbon::parse($purchaseDate)->format('Y');

            $company = \App\Models\Company::find($cid);
            $companyCode = $company?->code ?? '';

            $reference = trim((string)($data['reference'] ?? ''));
            if ($reference === '') {
                if ($companyCode) {
                    $reference = "PO-{$companyCode}-{$year}-" . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);
                } else {
                    $reference = "PO-{$year}-" . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);
                }
            }

            // Ensure uniqueness per company (friendly error)
            $refExists = Purchase::query()
                ->where('company_id', $cid)
                ->where('reference', $reference)
                ->exists();

            if ($refExists) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'reference' => 'Reference already exists for this company.',
                ]);
            }

            return Purchase::create([
                'company_id'    => $cid,
                'sequence_no'   => $nextSeq,
                'reference'     => $reference,

                'type'          => $data['type'],
                'supplier_id'   => $data['supplier_id'] ?? null,
                'product_id'    => (int) $data['product_id'],
                'depot_id'      => $data['depot_id'] ?? null,
                'purchase_date' => $purchaseDate,
                'qty'           => $data['qty'],
                'unit_price'    => $data['unit_price'],
                'currency'      => $data['currency'],
                'status'        => 'draft',
                'notes'         => $data['notes'] ?? null,
                'created_by'    => $u?->id,
                'updated_by'    => $u?->id,
            ]);
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Purchase created (draft).');
    }

    public function show(Purchase $purchase)
    {
        return view('purchases.show', compact('purchase'));
    }

    public function confirm(Purchase $purchase)
    {
        $u = auth()->user();

        if ($purchase->status !== 'draft') {
            return back()->with('error', 'Only draft purchases can be confirmed.');
        }

        DB::transaction(function () use ($purchase, $u) {
            // 1) Ensure batch exists
            if (!$purchase->batch_id) {
                $code = 'BATCH-' . now()->format('Y') . '-' . strtoupper(Str::random(6));
                $qty  = (float) $purchase->qty;
                $unit = (float) $purchase->unit_price;

                $batch = Batch::create([
                    'company_id'     => $purchase->company_id,
                    'product_id'     => $purchase->product_id,
                    'source_type'    => $purchase->type, // import|local_depot|cross_dock
                    'source_ref'     => 'purchase:' . $purchase->id,
                    'code'           => $code,
                    'name'           => null,
                    'supplier_id'    => $purchase->supplier_id,
                    'qty_purchased'  => $qty,
                    'qty_received'   => 0,
                    // remaining = AVAILABLE TO SELL (starts at 0 until received)
                    'qty_remaining'  => 0,
                    'total_cost'     => round($qty * $unit, 2),
                    'unit_cost'      => $qty > 0 ? $unit : 0,
                    'status'         => 'active',
                    'purchased_at'   => $purchase->purchase_date ? $purchase->purchase_date->startOfDay() : now(),
                    'created_by'     => $u?->id,
                    'updated_by'     => $u?->id,
                ]);

                $purchase->batch_id = $batch->id;
            }

            // 2) Branch behaviour
            if ($purchase->type === 'cross_dock') {
                // self-healing: ensure depot exists and get id
                $crossDockId = (int) $this->getOrCreateCrossDockDepotId($purchase->company_id, $u?->id);

                $qty  = (float) $purchase->qty;
                $unit = (float) $purchase->unit_price;
                $total = round($qty * $unit, 2);

                // prevent duplicate receipt if confirm retried
                $alreadyReceipted = DB::table('inventory_movements')
                    ->where('company_id', $purchase->company_id)
                    ->where('type', 'receipt')
                    ->where('ref_type', 'purchase')
                    ->where('ref_id', $purchase->id)
                    ->where('batch_id', $purchase->batch_id)
                    ->where('to_depot_id', $crossDockId)
                    ->exists();

                if (!$alreadyReceipted) {
                    DB::table('inventory_movements')->insert([
                        'company_id'    => $purchase->company_id,
                        'product_id'    => $purchase->product_id,
                        'type'          => 'receipt',
                        'ref_type'      => 'purchase',
                        'ref_id'        => $purchase->id,
                        'reference'     => 'purchase:' . $purchase->id,
                        'batch_id'      => $purchase->batch_id,
                        'from_depot_id' => null,
                        'to_depot_id'   => $crossDockId,
                        'qty'           => $qty,
                        'unit_cost'     => $unit,
                        'total_cost'    => $total,
                        'notes'         => 'Cross dock receipt from purchase confirm',
                        'created_by'    => $u?->id,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);

                    DB::table('batches')
                        ->where('id', $purchase->batch_id)
                        ->update([
                            'qty_received'  => DB::raw('qty_received + ' . (float) $qty),
                            'qty_remaining' => DB::raw('qty_remaining + ' . (float) $qty),
                            'updated_by'    => $u?->id,
                            'updated_at'    => now(),
                        ]);
                }
            }

            // local_depot + import: no receipt at confirm (receipt comes from depot stock/offload later)

            $purchase->status = 'confirmed';
            $purchase->updated_by = $u?->id;
            $purchase->save();
        });

        $msg = match ($purchase->type) {
            'cross_dock'  => "Purchase confirmed.\nBatch created.\nReceipted into CROSS DOCK.",
            'local_depot' => "Purchase confirmed.\nBatch created.\nNext: Receive into the selected depot from Depot Stock.",
            default       => "Purchase confirmed.\nBatch created.\nNext: Continue import workflow (nominations/offload).",
        };

        return redirect()->route('purchases.show', $purchase)
            ->with('status', $msg);
    }

    // Get or create the CROSS DOCK depot for the given company. Returns the depot ID.
    private function getOrCreateCrossDockDepotId(int $companyId, ?int $userId = null): int
    {
        $depot = Depot::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'name'       => 'CROSS DOCK',
            ],
            [
                'is_active'  => true,
                'is_system'  => true,
                'created_by' => $userId,
            ]
        );

        return (int) $depot->id;
    }
}