<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Transporter;
use App\Services\InventoryLedger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $saleId = (int) $request->query('sale', 0);

        $depots = Depot::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $sales = Sale::query()
            ->where('company_id', $cid)
            ->with(['depot', 'product', 'transporter'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $selected = null;
        if ($saleId > 0) {
            $selected = Sale::query()
                ->where('company_id', $cid)
                ->with(['depot', 'product', 'transporter', 'movement'])
                ->find($saleId);
        } else {
            // default select first item on page
            $selected = $sales->first();
        }

        $products = Product::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $transporters = Transporter::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('sales.index', compact('sales', 'selected', 'depots', 'products', 'transporters'));
    }

    public function store(Request $request)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $data = $request->validate([
            'depot_id'       => 'required|integer',
            'product_id'     => 'required|integer',
            'client_name'    => 'nullable|string|max:120',
            'sale_date'      => 'nullable|date',
            'qty'            => 'required|numeric|min:0.001',
            'unit_price'     => 'required|numeric|min:0',
            'currency'       => 'required|string|max:8',

            'delivery_mode'  => 'required|in:ex_depot,delivered',
            'transporter_id' => 'nullable|integer',
            'truck_no'       => 'nullable|string|max:32',
            'trailer_no'     => 'nullable|string|max:32',
            'waybill_no'     => 'nullable|string|max:64',
            'delivery_notes' => 'nullable|string',
        ]);

        // Validate depot belongs to company
        $depotOk = Depot::query()
            ->where('company_id', $cid)
            ->whereKey((int) $data['depot_id'])
            ->exists();
        if (!$depotOk) {
            return back()->withErrors(['depot_id' => 'Invalid depot for this company.'])->withInput();
        }

        // Validate product belongs to company
        $productOk = Product::query()
            ->where('company_id', $cid)
            ->whereKey((int) $data['product_id'])
            ->exists();
        if (!$productOk) {
            return back()->withErrors(['product_id' => 'Invalid product for this company.'])->withInput();
        }

        // If delivered, transporter optional for now, but if set must belong
        if (!empty($data['transporter_id'])) {
            $transporterOk = Transporter::query()
                ->where('company_id', $cid)
                ->whereKey((int) $data['transporter_id'])
                ->exists();
            if (!$transporterOk) {
                return back()->withErrors(['transporter_id' => 'Invalid transporter for this company.'])->withInput();
            }
        }

        $sale = DB::transaction(function () use ($cid, $u, $data) {
            $nextSeq = (int) Sale::query()
                ->where('company_id', $cid)
                ->lockForUpdate()
                ->max('sequence_no');

            $nextSeq = $nextSeq + 1;

            $saleDate = $data['sale_date'] ?? Carbon::today();
            $year = Carbon::parse($saleDate)->format('Y');

            $company = \App\Models\Company::find($cid);
            $companyCode = $company?->code ?? '';

            $reference = $companyCode
                ? "SO-{$companyCode}-{$year}-" . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT)
                : "SO-{$year}-" . str_pad((string)$nextSeq, 5, '0', STR_PAD_LEFT);

            $qty   = (float) $data['qty'];
            $unit  = (float) $data['unit_price'];
            $total = round($qty * $unit, 2);

            return Sale::create([
                'company_id'    => $cid,
                'depot_id'      => (int) $data['depot_id'],
                'product_id'    => (int) $data['product_id'],
                'client_name'   => $data['client_name'] ?? null,

                'sequence_no'   => $nextSeq,
                'reference'     => $reference,

                'sale_date'     => $saleDate,
                'qty'           => $qty,
                'unit_price'    => $unit,
                'currency'      => $data['currency'] ?? 'USD',
                'total'         => $total,

                'delivery_mode'  => $data['delivery_mode'],
                'transporter_id' => $data['delivery_mode'] === 'delivered' ? ($data['transporter_id'] ?? null) : null,
                'truck_no'       => $data['delivery_mode'] === 'delivered' ? ($data['truck_no'] ?? null) : null,
                'trailer_no'     => $data['delivery_mode'] === 'delivered' ? ($data['trailer_no'] ?? null) : null,
                'waybill_no'     => $data['delivery_mode'] === 'delivered' ? ($data['waybill_no'] ?? null) : null,
                'delivery_notes' => $data['delivery_mode'] === 'delivered' ? ($data['delivery_notes'] ?? null) : null,

                'status'        => 'draft',
                'created_by'    => $u?->id,
                'updated_by'    => $u?->id,
            ]);
        });

        return redirect()->route('sales.index', ['sale' => $sale->id])
            ->with('status', 'Sale created (draft).');
    }

public function post(Sale $sale, InventoryLedger $ledger)
{
    $u = auth()->user();

    if ($sale->status !== 'draft') {
        return back()->with('error', 'Only draft sales can be posted.');
    }

    if (!$sale->depot_id) {
        return back()->with('error', 'Depot is missing on this sale.');
    }

    $qty = (float) $sale->qty;
    if ($qty <= 0) {
        return back()->with('error', 'Quantity must be greater than zero.');
    }

    DB::transaction(function () use ($sale, $u, $ledger, $qty) {

        $result = $ledger->issue(
            [
                'company_id'    => (int) $sale->company_id,
                'product_id'    => (int) $sale->product_id,
                'from_depot_id' => (int) $sale->depot_id,
                'qty'           => $qty,

                'ref_type'      => 'sale',
                'ref_id'        => (int) $sale->id,
                'reference'     => 'sale:' . $sale->id,
                'notes'         => 'Sale issue (FIFO)',

                'created_by'    => $u?->id,
                'updated_by'    => $u?->id,
            ],
            [
                'type'          => 'issue',
                'ref_type'      => 'sale',
                'ref_id'        => (int) $sale->id,
                'from_depot_id' => (int) $sale->depot_id,
            ]
        );

        // If your issue() is idempotent and returns existing, still fine.
        $movement  = $result['movement'] ?? null;
        $cogsTotal = (float) ($result['cogs_total'] ?? 0);

        if (!$movement) {
            throw new \RuntimeException('Inventory issue failed: missing movement.');
        }

        $sale->inventory_movement_id = $movement->id;
        $sale->cogs_total = round($cogsTotal, 2);

        $gross = (float) $sale->total - (float) $sale->cogs_total;
        $sale->gross_profit = round($gross, 2);

        $sale->status    = 'posted';
        $sale->posted_by = $u?->id;
        $sale->posted_at = now();

        $sale->updated_by = $u?->id;
        $sale->save();
    });

    // I'd usually redirect to sales.show after posting:
    return redirect()->route('sales.index', $sale)
        ->with('status', "Sale posted.\nFIFO consumed stock.\nMovement created.");
}
}