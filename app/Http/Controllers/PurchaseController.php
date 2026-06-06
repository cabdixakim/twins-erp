<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Client;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Depot;
use App\Services\InventoryLedger;

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
        if ($status !== '' && in_array($status, ['draft', 'confirmed', 'nominated', 'received', 'transferred', 'dispatched', 'cancelled', 'voided'], true)) {
            $purchasesQuery->where('status', $status);
        }

        $purchases = $purchasesQuery
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('purchases.index', compact('purchases', 'supplierOptions'));
    }

    public function exportCsv(Request $request)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $q        = trim((string) $request->query('q', ''));
        $supplier = trim((string) $request->query('supplier', ''));
        $type     = trim((string) $request->query('type', ''));
        $status   = trim((string) $request->query('status', ''));

        $query = Purchase::query()
            ->where('company_id', $cid)
            ->with(['supplier', 'product', 'depot']);

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                if (ctype_digit($q)) {
                    $qq->orWhere('id', (int) $q)->orWhere('batch_id', (int) $q);
                }
                $qq->orWhere('id', 'like', '%' . $q . '%')
                   ->orWhereHas('supplier', fn($s) => $s->where('name', 'like', '%' . $q . '%'));
            });
        }
        if ($supplier !== '') {
            $query->where(function ($qq) use ($supplier) {
                if (ctype_digit($supplier)) {
                    $qq->where('supplier_id', (int) $supplier);
                } else {
                    $qq->whereHas('supplier', fn($s) => $s->where('name', $supplier));
                }
            });
        }
        if ($type !== '' && in_array($type, ['import', 'local_depot', 'cross_dock'], true)) {
            $query->where('type', $type);
        }
        if ($status !== '' && in_array($status, ['draft','confirmed','nominated','received','transferred','dispatched','cancelled','voided'], true)) {
            $query->where('status', $status);
        }

        $rows     = $query->latest('id')->get();
        $filename = 'purchases-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Reference', 'Date', 'Type', 'Status', 'Supplier', 'Product', 'Depot', 'Qty', 'Unit Cost', 'Est. Total', 'Currency']);
            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->id,
                    $p->reference ?? '',
                    optional($p->created_at)->format('Y-m-d') ?? '',
                    $p->type,
                    $p->status,
                    optional($p->supplier)->name ?? '',
                    optional($p->product)->name ?? '',
                    optional($p->depot)->name ?? '',
                    number_format((float) $p->qty, 3, '.', ''),
                    number_format((float) $p->unit_cost, 6, '.', ''),
                    number_format((float) $p->qty * (float) $p->unit_cost, 2, '.', ''),
                    $p->currency ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
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

        // Check for duplicate purchase reference (company_id + reference)
        if (!empty($data['reference'])) {
            $exists = \App\Models\Purchase::query()
                ->where('company_id', $cid)
                ->where('reference', $data['reference'])
                ->exists();
            if ($exists) {
                return back()->withErrors(['reference' => 'A purchase with this reference already exists for your company.'])->withInput();
            }
        }

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

            // Company-scoped sequence — lock the company row first (PostgreSQL forbids
            // FOR UPDATE with aggregate functions), then compute max without a row lock.
            DB::table('companies')->where('id', $cid)->lockForUpdate()->first();
            $nextSeq = (int) Purchase::query()
                ->where('company_id', $cid)
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
        $purchase->load(['supplier', 'product', 'depot', 'batch', 'creator']);

        $cid    = (int) $purchase->company_id;
        $depots = \App\Models\Depot::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->where(function ($q) { $q->whereNull('is_system')->orWhere('is_system', 0); })
            ->orderBy('name')
            ->get();

        // For import purchases: load partial delivery movements
        $importMovements = collect();
        if ($purchase->type === 'import' && $purchase->batch_id) {
            $importMovements = \App\Models\InventoryMovement::query()
                ->where('company_id', $cid)
                ->where('type', 'receipt')
                ->where('ref_type', 'purchase')
                ->where('ref_id', $purchase->id)
                ->where('reference', 'like', 'import-delivery:%')
                ->with('toDepot')
                ->orderBy('id')
                ->get();
        }

        $clients = Client::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Import logistics nomination + trucks
        $importNomination = null;
        if ($purchase->type === 'import') {
            $importNomination = $purchase->importNomination()->with(['transporter', 'trucks.depot'])->first();
        }

        $transporters = \App\Models\Transporter::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->where('type', 'intl')
            ->orderBy('name')
            ->get();

        $volumeUnit = \App\Models\Company::find($cid)?->volume_unit ?? 'L';

        return view('purchases.show', compact(
            'purchase', 'depots', 'importMovements', 'clients',
            'importNomination', 'transporters', 'volumeUnit'
        ));
    }

public function confirm(Purchase $purchase, InventoryLedger $ledger)
{
    $u = auth()->user();

    if ($purchase->status !== 'draft') {
        return back()->with('error', 'Only draft purchases can be confirmed.');
    }

    DB::transaction(function () use ($purchase, $u, $ledger) {
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
                'qty_remaining'  => 0, // available starts at 0 until received
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
            $crossDockId = (int) $this->getOrCreateCrossDockDepotId($purchase->company_id, $u?->id);

            $qty  = (float) $purchase->qty;
            $unit = (float) $purchase->unit_price;

            // Use ledger: movement + depot_stocks + batch qty update
            $ledger->receipt(
                [
                    'company_id'  => $purchase->company_id,
                    'product_id'  => $purchase->product_id,
                    'to_depot_id' => $crossDockId,
                    'batch_id'    => $purchase->batch_id,
                    'qty'         => $qty,
                    'unit_cost'   => $unit,
                    'total_cost'  => round($qty * $unit, 2),

                    'ref_type'    => 'purchase',
                    'ref_id'      => $purchase->id,
                    'reference'   => 'purchase:' . $purchase->id,
                    'notes'       => 'Cross dock receipt from purchase confirm',

                    'created_by'  => $u?->id,
                    'updated_by'  => $u?->id,
                ],
                // Idempotency: prevents duplicate receipts on retries
                [
                    'type'        => 'receipt',
                    'ref_type'    => 'purchase',
                    'ref_id'      => $purchase->id,
                    'batch_id'    => $purchase->batch_id,
                    'to_depot_id' => $crossDockId,
                ]
            );
        }

        // local_depot + import: no receipt at confirm (receipt comes later)

        $purchase->status = 'confirmed';
        $purchase->updated_by = $u?->id;
        $purchase->save();
    });

    $msg = match ($purchase->type) {
        'cross_dock'  => "Purchase confirmed.\nBatch created.\nReceipted into CROSS DOCK.",
        'local_depot' => "Purchase confirmed.\nBatch created.\nNext: Receive into the selected depot.",
        default       => "Purchase confirmed.\nBatch created.\nNext: Continue import workflow (nominations/offload).",
    };

    return redirect()->route('purchases.show', $purchase)
        ->with('status', $msg);
}


// Only for local depot purchases: creates a receipt movement into the selected depot (ownership change) and updates batch qtys. Idempotent (safe to retry if something fails after receipt creation).

public function receive(Purchase $purchase, InventoryLedger $ledger)
{
    $u = auth()->user();

    if ($purchase->status !== 'confirmed') {
        return back()->with('error', 'Only confirmed purchases can be received.');
    }

    if ($purchase->type !== 'local_depot') {
        return back()->with('error', 'Only local depot purchases can be received into depot.');
    }

    if (!$purchase->depot_id) {
        return back()->with('error', 'Depot is missing on this purchase.');
    }

    DB::transaction(function () use ($purchase, $u, $ledger) {

        // Safety: ensure batch exists (should already exist, but never trust)
        if (!$purchase->batch_id) {
            $code = 'BATCH-' . now()->format('Y') . '-' . strtoupper(Str::random(6));
            $qty  = (float) $purchase->qty;
            $unit = (float) $purchase->unit_price;

            $batch = Batch::create([
                'company_id'     => $purchase->company_id,
                'product_id'     => $purchase->product_id,
                'source_type'    => $purchase->type,
                'source_ref'     => 'purchase:' . $purchase->id,
                'code'           => $code,
                'name'           => null,
                'supplier_id'    => $purchase->supplier_id,
                'qty_purchased'  => $qty,
                'qty_received'   => 0,
                'qty_remaining'  => 0,
                'total_cost'     => round($qty * $unit, 2),
                'unit_cost'      => $qty > 0 ? $unit : 0,
                'status'         => 'active',
                'purchased_at'   => $purchase->purchase_date ? $purchase->purchase_date->startOfDay() : now(),
                'created_by'     => $u?->id,
                'updated_by'     => $u?->id,
            ]);

            // attach batch, don't set received here — ledger will do it
            $purchase->batch_id = $batch->id;
            $purchase->updated_by = $u?->id;
            $purchase->save();
        }

        $qty  = (float) $purchase->qty;
        $unit = (float) $purchase->unit_price;

        // Ledger handles: inventory_movements + depot_stocks + batch qty updates (and idempotency)
        $ledger->receipt(
            [
                'company_id'   => (int) $purchase->company_id,
                'product_id'   => (int) $purchase->product_id,
                'to_depot_id'  => (int) $purchase->depot_id,
                'batch_id'     => (int) $purchase->batch_id,
                'qty'          => $qty,
                // ledger will prefer batch unit_cost if batch exists, but keep for completeness
                'unit_cost'    => $unit,
                'total_cost'   => round($qty * $unit, 2),

                'ref_type'     => 'purchase',
                'ref_id'       => (int) $purchase->id,
                'reference'    => 'purchase:' . $purchase->id,
                'notes'        => 'Depot receipt from purchase receive',

                'created_by'   => $u?->id,
                'updated_by'   => $u?->id,
            ],
            [
                // Idempotency key (safe retries)
                'type'        => 'receipt',
                'ref_type'    => 'purchase',
                'ref_id'      => (int) $purchase->id,
                'batch_id'    => (int) $purchase->batch_id,
                'to_depot_id' => (int) $purchase->depot_id,
            ]
        );

        // Mark as received (make sure your app recognises this status in filters/UI)
        $purchase->status = 'received';
        $purchase->updated_by = $u?->id;
        $purchase->save();
    });

    return redirect()->route('purchases.show', $purchase)
        ->with('status', "Purchase received into depot.\nDepot stock updated (FIFO-ready).");
}

    /**
     * Undo a depot receipt for a local_depot purchase.
     * Reverses the inventory movement, restores batch quantities, and sets status back to confirmed.
     */
    public function undoReceipt(Purchase $purchase)
    {
        $u = auth()->user();

        if ($purchase->status !== 'received') {
            return back()->with('error', 'Only received purchases can have their receipt undone.');
        }

        if ($purchase->type !== 'local_depot') {
            return back()->with('error', 'Undo receipt is only available for local depot purchases.');
        }

        DB::transaction(function () use ($purchase, $u) {
            // Find the original receipt movement
            $movement = \App\Models\InventoryMovement::query()
                ->where('company_id', $purchase->company_id)
                ->where('type', 'receipt')
                ->where('ref_type', 'purchase')
                ->where('ref_id', $purchase->id)
                ->where('batch_id', $purchase->batch_id)
                ->where('to_depot_id', $purchase->depot_id)
                ->latest('id')
                ->first();

            if ($movement) {
                $qty = (float) $movement->qty;

                // Reverse depot stock
                \App\Models\DepotStock::query()
                    ->where('company_id', $purchase->company_id)
                    ->where('depot_id', $purchase->depot_id)
                    ->where('product_id', $purchase->product_id)
                    ->where('batch_id', $purchase->batch_id)
                    ->update([
                        'qty_on_hand' => DB::raw('GREATEST(0, qty_on_hand - ' . $qty . ')'),
                        'updated_at'  => now(),
                    ]);

                // Reverse batch quantities
                \App\Models\Batch::query()
                    ->where('company_id', $purchase->company_id)
                    ->whereKey($purchase->batch_id)
                    ->update([
                        'qty_received'  => DB::raw('GREATEST(0, qty_received - ' . $qty . ')'),
                        'qty_remaining' => DB::raw('GREATEST(0, qty_remaining - ' . $qty . ')'),
                        'updated_at'    => now(),
                    ]);

                // Mark original movement as reversed
                $movement->update(['notes' => ($movement->notes ? $movement->notes . ' | ' : '') . 'REVERSED by undo-receipt']);
            }

            $purchase->status     = 'confirmed';
            $purchase->actioned_at = null;
            $purchase->actioned_by = null;
            $purchase->action_note = 'Receipt reversed';
            $purchase->updated_by  = $u?->id;
            $purchase->save();
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Receipt reversed. Purchase is back to Confirmed — ready to receive again.');
    }

    /**
     * Transfer stock from CROSS DOCK depot into a physical depot.
     */
    public function crossDockTransfer(Purchase $purchase, InventoryLedger $ledger)
    {
        $u   = auth()->user();
        $cid = (int) $purchase->company_id;

        if ($purchase->status !== 'confirmed' || $purchase->type !== 'cross_dock') {
            return back()->with('error', 'Only confirmed cross-dock purchases can be transferred.');
        }

        $request  = request();
        $depotId  = (int) $request->input('depot_id');
        $qty      = (float) $request->input('qty', $purchase->qty);
        $note     = trim((string) $request->input('note', ''));

        if (!$depotId) {
            return back()->withErrors(['depot_id' => 'Please select a destination depot.']);
        }

        $depotOk = \App\Models\Depot::query()
            ->where('company_id', $cid)
            ->whereKey($depotId)
            ->where('is_active', true)
            ->where(function ($q) { $q->whereNull('is_system')->orWhere('is_system', 0); })
            ->exists();

        if (!$depotOk) {
            return back()->withErrors(['depot_id' => 'Invalid destination depot.']);
        }

        DB::transaction(function () use ($purchase, $ledger, $u, $cid, $depotId, $qty, $note) {
            $crossDockDepotId = $this->getOrCreateCrossDockDepotId($cid, $u?->id);

            // Issue from CROSS DOCK
            $ledger->issue([
                'company_id'   => $cid,
                'product_id'   => (int) $purchase->product_id,
                'from_depot_id'=> $crossDockDepotId,
                'batch_id'     => (int) $purchase->batch_id,
                'qty'          => $qty,
                'ref_type'     => 'purchase',
                'ref_id'       => (int) $purchase->id,
                'reference'    => 'cross-dock-transfer:' . $purchase->id,
                'notes'        => 'Cross-dock transfer to depot #' . $depotId . ($note ? ': ' . $note : ''),
                'created_by'   => $u?->id,
                'updated_by'   => $u?->id,
            ], [
                'type'          => 'issue',
                'ref_type'      => 'purchase',
                'ref_id'        => (int) $purchase->id,
                'from_depot_id' => $crossDockDepotId,
            ]);

            // Receipt into target depot
            $ledger->receipt([
                'company_id'  => $cid,
                'product_id'  => (int) $purchase->product_id,
                'to_depot_id' => $depotId,
                'batch_id'    => (int) $purchase->batch_id,
                'qty'         => $qty,
                'unit_cost'   => (float) $purchase->unit_price,
                'ref_type'    => 'purchase',
                'ref_id'      => (int) $purchase->id,
                'reference'   => 'cross-dock-transfer:' . $purchase->id,
                'notes'       => 'Cross-dock transfer from CROSS DOCK' . ($note ? ': ' . $note : ''),
                'created_by'  => $u?->id,
                'updated_by'  => $u?->id,
            ], [
                'type'        => 'receipt',
                'ref_type'    => 'purchase',
                'ref_id'      => (int) $purchase->id,
                'to_depot_id' => $depotId,
                'batch_id'    => (int) $purchase->batch_id,
            ]);

            $purchase->status      = 'transferred';
            $purchase->depot_id    = $depotId;
            $purchase->actioned_at = now();
            $purchase->actioned_by = $u?->id;
            $purchase->action_note = $note ?: 'Transferred to depot';
            $purchase->updated_by  = $u?->id;
            $purchase->save();
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Stock transferred from Cross Dock into the selected depot.');
    }

    /**
     * Mark a cross-dock purchase as dispatched (straight-out delivery).
     * Inventory issue from CROSS DOCK is posted; status becomes dispatched.
     */
    public function crossDockDispatch(Purchase $purchase, InventoryLedger $ledger)
    {
        $u   = auth()->user();
        $cid = (int) $purchase->company_id;

        if ($purchase->status !== 'confirmed' || $purchase->type !== 'cross_dock') {
            return back()->with('error', 'Only confirmed cross-dock purchases can be dispatched.');
        }

        $note     = trim((string) request()->input('note', ''));
        $qty      = (float) request()->input('qty', $purchase->qty);
        $clientId = (int) request()->input('client_id', 0) ?: null;

        // Resolve client name for the movement note
        $clientName = null;
        if ($clientId) {
            $clientName = Client::query()
                ->where('company_id', $cid)
                ->where('id', $clientId)
                ->value('name');
        }

        DB::transaction(function () use ($purchase, $ledger, $u, $cid, $qty, $note, $clientId, $clientName) {
            $crossDockDepotId = $this->getOrCreateCrossDockDepotId($cid, $u?->id);

            $movementNotes = 'Cross-dock direct dispatch';
            if ($clientName) $movementNotes .= ' → ' . $clientName;
            if ($note) $movementNotes .= ': ' . $note;

            // Issue from CROSS DOCK (stock leaves the system)
            $ledger->issue([
                'company_id'    => $cid,
                'product_id'    => (int) $purchase->product_id,
                'from_depot_id' => $crossDockDepotId,
                'batch_id'      => (int) $purchase->batch_id,
                'qty'           => $qty,
                'ref_type'      => 'purchase',
                'ref_id'        => (int) $purchase->id,
                'reference'     => 'cross-dock-dispatch:' . $purchase->id,
                'notes'         => $movementNotes,
                'created_by'    => $u?->id,
                'updated_by'    => $u?->id,
            ], [
                'type'          => 'issue',
                'ref_type'      => 'purchase',
                'ref_id'        => (int) $purchase->id,
                'from_depot_id' => $crossDockDepotId,
            ]);

            $purchase->status      = 'dispatched';
            $purchase->client_id   = $clientId;
            $purchase->actioned_at = now();
            $purchase->actioned_by = $u?->id;
            $purchase->action_note = ($clientName ? 'Client: ' . $clientName . '. ' : '') . ($note ?: 'Dispatched directly');
            $purchase->updated_by  = $u?->id;
            $purchase->save();
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Cross-dock stock dispatched. Inventory issued from Cross Dock.');
    }

    /**
     * Show edit form for a draft purchase.
     */
    public function edit(Purchase $purchase)
    {
        if ($purchase->status !== 'draft') {
            return redirect()->route('purchases.show', $purchase)
                ->with('error', 'Only draft purchases can be edited.');
        }

        $purchase->load(['supplier', 'product', 'depot']);
        $cid = (int) $purchase->company_id;

        $suppliers = Supplier::query()->where('company_id', $cid)->orderBy('name')->get();
        $products  = Product::query()->where('company_id', $cid)->where('is_active', true)->orderBy('name')->get();
        $depots    = DB::table('depots')
            ->where('company_id', $cid)
            ->where('is_active', 1)
            ->where(function ($q) { $q->whereNull('is_system')->orWhere('is_system', 0); })
            ->orderBy('name')
            ->get();

        return view('purchases.edit', compact('purchase', 'suppliers', 'products', 'depots'));
    }

    /**
     * Update a draft purchase.
     */
    public function update(Request $request, Purchase $purchase)
    {
        $u   = auth()->user();
        $cid = (int) $purchase->company_id;

        if ($purchase->status !== 'draft') {
            return back()->with('error', 'Only draft purchases can be edited.');
        }

        $data = $request->validate([
            'supplier_id'   => 'nullable|integer',
            'product_id'    => 'required|integer',
            'depot_id'      => 'nullable|integer',
            'purchase_date' => 'nullable|date',
            'qty'           => 'required|numeric|min:0.001',
            'unit_price'    => 'required|numeric|min:0',
            'currency'      => 'required|string|max:8',
            'notes'         => 'nullable|string',
            'reference'     => 'nullable|string|max:64',
        ]);

        // Allow same reference on this purchase, but block collision with others
        if (!empty($data['reference']) && $data['reference'] !== $purchase->reference) {
            $exists = Purchase::query()
                ->where('company_id', $cid)
                ->where('reference', $data['reference'])
                ->where('id', '!=', $purchase->id)
                ->exists();
            if ($exists) {
                return back()->withErrors(['reference' => 'Reference already exists for this company.'])->withInput();
            }
        }

        // Depot rules: import/cross_dock must not store a depot_id
        if ($purchase->type !== 'local_depot') {
            $data['depot_id'] = null;
        } elseif (empty($data['depot_id'])) {
            return back()->withErrors(['depot_id' => 'Depot is required for local depot purchases.'])->withInput();
        }

        $purchase->fill($data);
        $purchase->updated_by = $u?->id;
        $purchase->save();

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Purchase updated.');
    }

    /**
     * Cancel a purchase (draft, confirmed, or nominated-import with no deliveries).
     * For cross_dock confirmed: automatically reverses the CROSS DOCK receipt.
     */
    public function cancel(Purchase $purchase)
    {
        $u      = auth()->user();
        $reason = trim((string) request()->input('reason', ''));

        $cancelable = ['draft', 'confirmed', 'nominated'];
        if (!in_array($purchase->status, $cancelable, true)) {
            return back()->with('error', 'This purchase cannot be cancelled in its current status.');
        }

        // Block nominated import with posted deliveries
        if ($purchase->type === 'import' && $purchase->status === 'nominated'
            && ((float) $purchase->qty_delivered) > 0) {
            return back()->with('error', 'Cannot cancel: partial deliveries have already been posted.');
        }

        DB::transaction(function () use ($purchase, $u, $reason) {
            // Cross-dock confirmed → reverse the CROSS DOCK receipt
            if ($purchase->type === 'cross_dock' && $purchase->status === 'confirmed' && $purchase->batch_id) {
                $crossDockDepotId = $this->getOrCreateCrossDockDepotId($purchase->company_id, $u?->id);

                $movement = \App\Models\InventoryMovement::query()
                    ->where('company_id', $purchase->company_id)
                    ->where('type', 'receipt')
                    ->where('ref_type', 'purchase')
                    ->where('ref_id', $purchase->id)
                    ->where('to_depot_id', $crossDockDepotId)
                    ->latest('id')
                    ->first();

                if ($movement) {
                    $qty = (float) $movement->qty;

                    \App\Models\DepotStock::query()
                        ->where('company_id', $purchase->company_id)
                        ->where('depot_id', $crossDockDepotId)
                        ->where('product_id', $purchase->product_id)
                        ->where('batch_id', $purchase->batch_id)
                        ->update(['qty_on_hand' => DB::raw('GREATEST(0, qty_on_hand - ' . $qty . ')'), 'updated_at' => now()]);

                    \App\Models\Batch::query()
                        ->where('company_id', $purchase->company_id)
                        ->whereKey($purchase->batch_id)
                        ->update([
                            'qty_received'  => DB::raw('GREATEST(0, qty_received - ' . $qty . ')'),
                            'qty_remaining' => DB::raw('GREATEST(0, qty_remaining - ' . $qty . ')'),
                            'updated_at'    => now(),
                        ]);

                    $movement->update(['notes' => trim(($movement->notes ?? '') . ' | CANCELLED: ' . $reason)]);
                }
            }

            $purchase->status      = 'cancelled';
            $purchase->action_note = $reason ?: 'Cancelled';
            $purchase->actioned_at = now();
            $purchase->actioned_by = $u?->id;
            $purchase->updated_by  = $u?->id;
            $purchase->save();
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Purchase cancelled.' . ($reason ? ' Reason: ' . $reason : ''));
    }

    /**
     * Void a received local_depot purchase (return to seller).
     * Reverses the depot receipt and marks as voided.
     */
    public function void(Purchase $purchase)
    {
        $u      = auth()->user();
        $reason = trim((string) request()->input('reason', ''));

        if ($purchase->status !== 'received' || $purchase->type !== 'local_depot') {
            return back()->with('error', 'Only received local depot purchases can be returned to seller.');
        }

        DB::transaction(function () use ($purchase, $u, $reason) {
            $movement = \App\Models\InventoryMovement::query()
                ->where('company_id', $purchase->company_id)
                ->where('type', 'receipt')
                ->where('ref_type', 'purchase')
                ->where('ref_id', $purchase->id)
                ->where('batch_id', $purchase->batch_id)
                ->where('to_depot_id', $purchase->depot_id)
                ->latest('id')
                ->first();

            if ($movement) {
                $qty = (float) $movement->qty;

                \App\Models\DepotStock::query()
                    ->where('company_id', $purchase->company_id)
                    ->where('depot_id', $purchase->depot_id)
                    ->where('product_id', $purchase->product_id)
                    ->where('batch_id', $purchase->batch_id)
                    ->update(['qty_on_hand' => DB::raw('GREATEST(0, qty_on_hand - ' . $qty . ')'), 'updated_at' => now()]);

                \App\Models\Batch::query()
                    ->where('company_id', $purchase->company_id)
                    ->whereKey($purchase->batch_id)
                    ->update([
                        'qty_received'  => DB::raw('GREATEST(0, qty_received - ' . $qty . ')'),
                        'qty_remaining' => DB::raw('GREATEST(0, qty_remaining - ' . $qty . ')'),
                        'updated_at'    => now(),
                    ]);

                $movement->update(['notes' => trim(($movement->notes ?? '') . ' | VOIDED/RETURNED: ' . $reason)]);
            }

            $purchase->status      = 'voided';
            $purchase->action_note = $reason ?: 'Returned to seller';
            $purchase->actioned_at = now();
            $purchase->actioned_by = $u?->id;
            $purchase->updated_by  = $u?->id;
            $purchase->save();
        });

        return redirect()->route('purchases.show', $purchase)
            ->with('status', 'Purchase voided. Stock reversed and returned to seller.');
    }

    /**
     * Nominate a vessel for a confirmed import purchase.
     * Records shipping details and moves status → nominated.
     */
    public function nominate(Purchase $purchase)
    {
        $u = auth()->user();

        if ($purchase->type !== 'import') {
            return back()->with('error', 'Only import purchases can be nominated.');
        }

        if ($purchase->status !== 'confirmed') {
            return back()->with('error', 'Only confirmed purchases can be nominated.');
        }

        $data = request()->validate([
            'vessel_name'    => 'required|string|max:255',
            'voyage_no'      => 'nullable|string|max:100',
            'loading_port'   => 'nullable|string|max:255',
            'discharge_port' => 'nullable|string|max:255',
            'bl_number'      => 'nullable|string|max:100',
            'bl_date'        => 'nullable|date',
            'eta_date'       => 'nullable|date',
        ]);

        $purchase->fill($data);
        $purchase->status     = 'nominated';
        $purchase->updated_by = $u?->id;
        $purchase->save();

        $eta = isset($data['eta_date']) ? ' · ETA ' . $data['eta_date'] : '';
        return redirect()->route('purchases.show', $purchase)
            ->with('status', "Vessel nominated: {$data['vessel_name']}{$eta}.\nNext: deliver cargo to depot(s).");
    }

    /**
     * Deliver a partial or full import shipment into a physical depot.
     * Creates a receipt movement; accumulates qty_delivered; auto-closes when fully delivered.
     */
    public function importDeliver(Purchase $purchase, InventoryLedger $ledger)
    {
        $u   = auth()->user();
        $cid = (int) $purchase->company_id;

        if ($purchase->type !== 'import') {
            return back()->with('error', 'Only import purchases can be delivered.');
        }

        if ($purchase->status !== 'nominated') {
            return back()->with('error', 'Only nominated purchases can be delivered to depot.');
        }

        $data = request()->validate([
            'depot_id' => 'required|integer',
            'qty'      => 'required|numeric|min:0.001',
            'note'     => 'nullable|string|max:255',
        ]);

        $depotId = (int) $data['depot_id'];
        $qty     = (float) $data['qty'];
        $note    = trim((string) ($data['note'] ?? ''));

        $depotOk = \App\Models\Depot::query()
            ->where('company_id', $cid)
            ->whereKey($depotId)
            ->where('is_active', true)
            ->where(function ($q) { $q->whereNull('is_system')->orWhere('is_system', 0); })
            ->exists();

        if (!$depotOk) {
            return back()->withErrors(['depot_id' => 'Invalid or inactive depot.']);
        }

        DB::transaction(function () use ($purchase, $ledger, $u, $cid, $depotId, $qty, $note) {
            $unit        = (float) $purchase->unit_price;
            // Unique reference per delivery so idempotency won't block multiple partial deliveries
            $deliveryRef = 'import-delivery:' . $purchase->id . ':' . now()->format('YmdHisu');

            $ledger->receipt(
                [
                    'company_id'  => $cid,
                    'product_id'  => (int) $purchase->product_id,
                    'to_depot_id' => $depotId,
                    'batch_id'    => (int) $purchase->batch_id,
                    'qty'         => $qty,
                    'unit_cost'   => $unit,
                    'total_cost'  => round($qty * $unit, 2),
                    'ref_type'    => 'purchase',
                    'ref_id'      => (int) $purchase->id,
                    'reference'   => $deliveryRef,
                    'notes'       => 'Import delivery to depot' . ($note ? ': ' . $note : ''),
                    'created_by'  => $u?->id,
                    'updated_by'  => $u?->id,
                ],
                [] // No idempotency guard — multiple partial deliveries are intentional
            );

            $purchase->qty_delivered = round(((float) $purchase->qty_delivered) + $qty, 3);

            if ($purchase->qty_delivered >= (float) $purchase->qty) {
                $purchase->status = 'received';
            }

            $purchase->updated_by = $u?->id;
            $purchase->save();
        });

        $delivered = number_format((float) $purchase->qty_delivered, 3);
        $total     = number_format((float) $purchase->qty, 3);

        $msg = $purchase->status === 'received'
            ? "Delivery posted.\nPurchase fully received ({$delivered} L) — stock is now in depot."
            : "Delivery posted (" . number_format($qty, 3) . " L).\n{$delivered} / {$total} L delivered so far.";

        return redirect()->route('purchases.show', $purchase)->with('status', $msg);
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