<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Batch;
use App\Models\Client;
use App\Models\Company;
use App\Models\Depot;
use App\Models\DepotStock;
use App\Models\Invoice;
use App\Models\InventoryConsumption;
use App\Models\InventoryMovement;
use App\Models\PettyCashAccount;
use App\Models\PettyCashTransaction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Transporter;
use App\Models\ClientLedgerEntry;
use App\Models\TransporterLedgerEntry;
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

        $filterStatus = $request->query('status', '');
        $filterSearch = $request->query('q', '');

        $sales = Sale::query()
            ->where('company_id', $cid)
            ->with(['depot', 'product', 'transporter', 'invoice'])
            ->when($filterStatus, fn($q) => $q->where('status', $filterStatus))
            ->when($filterSearch, fn($q) => $q->where(function ($q2) use ($filterSearch) {
                $q2->where('reference', 'ilike', "%{$filterSearch}%")
                   ->orWhere('client_name', 'ilike', "%{$filterSearch}%");
            }))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $selected = null;
        if ($saleId > 0) {
            $selected = Sale::query()
                ->where('company_id', $cid)
                ->with(['depot', 'product', 'transporter', 'movement', 'invoice'])
                ->find($saleId);
        }

        $products = Product::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $transporters = Transporter::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('type', 'local')->orWhereNull('type');
            })
            ->orderBy('name')
            ->get();

        $clients = Client::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $pettyCashAccounts = PettyCashAccount::query()
            ->where('company_id', $cid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $prefill = [
            'open'       => (bool) $request->boolean('open_sale'),
            'depot_id'   => (int) $request->query('from_depot', 0),
            'product_id' => (int) $request->query('from_product', 0),
        ];

        $company       = Company::find($cid);
        $costingMethod = $company?->costing_method ?? 'weighted_average';
        $volumeUnit    = $company?->volume_unit ?? 'L';

        // For specific_lot: pass active batches so the sale form can show a picker
        $batches = $costingMethod === 'specific_lot'
            ? Batch::where('company_id', $cid)
                ->where('status', 'active')
                ->whereColumn('qty_remaining', '>', DB::raw('0'))
                ->orderBy('code')
                ->get(['id', 'code', 'product_id', 'qty_remaining', 'purchased_at'])
            : collect();

        return view('sales.index', compact('sales', 'selected', 'depots', 'products', 'transporters', 'clients', 'pettyCashAccounts', 'prefill', 'costingMethod', 'batches', 'volumeUnit'));
    }

    public function exportCsv()
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $rows     = Sale::where('company_id', $cid)
            ->with(['product', 'depot', 'transporter'])
            ->latest('id')
            ->get();

        $filename = 'sales-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Reference', 'Sale Date', 'Status', 'Client', 'Product', 'Depot', 'Qty', 'Unit Price', 'Total', 'COGS', 'Transporter']);
            foreach ($rows as $s) {
                fputcsv($out, [
                    $s->id,
                    $s->reference ?? '',
                    $s->sale_date ?? '',
                    $s->status,
                    $s->client_name ?? '',
                    optional($s->product)->name ?? '',
                    optional($s->depot)->name ?? '',
                    number_format((float) $s->qty, 3, '.', ''),
                    number_format((float) $s->unit_price, 6, '.', ''),
                    number_format((float) $s->total, 2, '.', ''),
                    number_format((float) $s->cogs_total, 2, '.', ''),
                    optional($s->transporter)->name ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        $data = $request->validate([
            'depot_id'       => 'required|integer',
            'product_id'     => 'required|integer',
            'client_id'      => 'nullable|integer',
            'client_name'    => 'nullable|string|max:120',
            'sale_date'      => 'nullable|date',
            'qty'            => 'required|numeric|min:0.001',
            'unit_price'     => 'required|numeric|min:0',
            'currency'       => 'required|string|max:8',
            'reference'      => 'nullable|string|max:64',
            'batch_id'       => 'nullable|integer',

            'delivery_mode'    => 'required|in:ex_depot,delivered',
            'transporter_id'   => 'nullable|integer',
            'truck_no'         => 'nullable|string|max:32',
            'trailer_no'       => 'nullable|string|max:32',
            'waybill_no'       => 'nullable|string|max:64',
            'delivery_notes'   => 'nullable|string',
            'freight_amount'   => 'nullable|numeric|min:0',
            'freight_currency' => 'nullable|string|max:8',
            'driver_name'      => 'nullable|string|max:150',
            'seal_numbers'     => 'nullable|string',
            'temperature'      => 'nullable|numeric|min:-20|max:100',
            'density'          => 'nullable|numeric|min:0|max:2',

            'advance_account_id' => 'nullable|integer',
            'trip_advance'       => 'nullable|numeric|min:0',
            'fuel_advance'       => 'nullable|numeric|min:0',
            'advance_currency'   => 'nullable|string|max:8',
        ]);

        // Check for duplicate sale reference (company_id + reference)
        if (!empty($data['reference'])) {
            $exists = \App\Models\Sale::query()
                ->where('company_id', $cid)
                ->where('reference', $data['reference'])
                ->exists();
            if ($exists) {
                return back()->withErrors(['reference' => 'A sale with this reference already exists for your company.'])->withInput();
            }
        }

        $depotOk = Depot::query()->where('company_id', $cid)->whereKey((int) $data['depot_id'])->exists();
        if (!$depotOk) return back()->withErrors(['depot_id' => 'Invalid depot for this company.'])->withInput();

        $productOk = Product::query()->where('company_id', $cid)->whereKey((int) $data['product_id'])->exists();
        if (!$productOk) return back()->withErrors(['product_id' => 'Invalid product for this company.'])->withInput();

        if (!empty($data['transporter_id'])) {
            $transporterOk = Transporter::query()->where('company_id', $cid)->whereKey((int) $data['transporter_id'])->exists();
            if (!$transporterOk) return back()->withErrors(['transporter_id' => 'Invalid transporter for this company.'])->withInput();
        }

        // Check for sufficient stock before allowing new sale
        $qty = (float) $data['qty'];
        $availableTotal = 0.0;
        $layers = \App\Models\DepotStock::query()
            ->where('company_id', $cid)
            ->where('depot_id', (int) $data['depot_id'])
            ->where('product_id', (int) $data['product_id'])
            ->whereRaw('(qty_on_hand - qty_reserved) > 0')
            ->get();
        foreach ($layers as $l) {
            $availableTotal += max(0, (float) $l->qty_on_hand - (float) $l->qty_reserved);
        }
        if ($availableTotal + 1e-9 < $qty) {
            return back()->withErrors(['qty' => 'Insufficient stock in depot for this product.'])->withInput();
        }

        // Credit limit check
        if (!empty($data['client_id'])) {
            $limitError = $this->checkCreditLimit(
                (int) $data['client_id'],
                $cid,
                round((float) $data['qty'] * (float) $data['unit_price'], 2),
                $data['currency'] ?? 'USD'
            );
            if ($limitError) {
                return back()->withErrors(['client_id' => $limitError])->withInput();
            }
        }

        $sale = DB::transaction(function () use ($cid, $u, $data) {
            // Lock the company row to serialise concurrent sequence number generation.
            // PostgreSQL rejects FOR UPDATE on aggregate queries, so we lock a sentinel
            // row first, then read MAX without a lock — only one transaction proceeds at a time.
            DB::table('companies')->where('id', $cid)->lockForUpdate()->first();

            $nextSeq = (int) Sale::query()
                ->where('company_id', $cid)
                ->max('sequence_no') + 1;

            $saleDate = $data['sale_date'] ?? Carbon::today();
            $year = Carbon::parse($saleDate)->format('Y');

            $company = Company::find($cid);
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
                'client_id'     => $data['client_id'] ? (int) $data['client_id'] : null,
                'client_name'   => $data['client_name'] ?? null,
                'batch_id'      => !empty($data['batch_id']) ? (int) $data['batch_id'] : null,

                'sequence_no'   => $nextSeq,
                'reference'     => $reference,

                'sale_date'     => $saleDate,
                'qty'           => $qty,
                'unit_price'    => $unit,
                'currency'      => $data['currency'] ?? 'USD',
                'total'         => $total,

                'delivery_mode'    => $data['delivery_mode'],
                'transporter_id'   => !empty($data['transporter_id']) ? (int) $data['transporter_id'] : null,
                'truck_no'         => $data['delivery_mode'] === 'delivered' ? ($data['truck_no'] ?? null) : null,
                'trailer_no'       => $data['delivery_mode'] === 'delivered' ? ($data['trailer_no'] ?? null) : null,
                'waybill_no'       => $data['delivery_mode'] === 'delivered' ? ($data['waybill_no'] ?? null) : null,
                'delivery_notes'   => $data['delivery_mode'] === 'delivered' ? ($data['delivery_notes'] ?? null) : null,
                'freight_amount'   => $data['delivery_mode'] === 'delivered' && !empty($data['transporter_id']) && !empty($data['freight_amount'])
                                        ? (float) $data['freight_amount'] : null,
                'freight_currency' => $data['delivery_mode'] === 'delivered' && !empty($data['transporter_id'])
                                        ? ($data['freight_currency'] ?? 'USD') : null,
                'driver_name'      => $data['delivery_mode'] === 'delivered' ? ($data['driver_name'] ?? null) : null,
                'seal_numbers'     => $data['delivery_mode'] === 'delivered' ? ($data['seal_numbers'] ?? null) : null,
                'temperature'      => $data['delivery_mode'] === 'delivered' ? (isset($data['temperature']) ? (float) $data['temperature'] : 20.0) : null,
                'density'          => $data['delivery_mode'] === 'delivered' ? (isset($data['density']) && $data['density'] !== '' ? (float) $data['density'] : null) : null,

                'advance_account_id' => !empty($data['advance_account_id']) ? (int) $data['advance_account_id'] : null,
                'trip_advance'       => !empty($data['trip_advance'])       ? (float) $data['trip_advance'] : null,
                'fuel_advance'       => !empty($data['fuel_advance'])       ? (float) $data['fuel_advance'] : null,
                'advance_currency'   => $data['advance_currency'] ?? 'USD',

                'status'        => 'draft',
                'created_by'    => $u?->id,
                'updated_by'    => $u?->id,
            ]);
        });

        // Post driver advances to petty cash if provided
        $this->postDriverAdvances($sale, $data, $cid);

        return redirect()->route('sales.index', ['sale' => $sale->id])
            ->with('status', 'Sale created (draft).');
    }

    public function update(Request $request, Sale $sale)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);

        if ((int)$sale->company_id !== $cid) {
            abort(404);
        }

        if ($sale->status !== 'draft') {
            return back()->with('error', 'Only draft sales can be edited.');
        }

        $data = $request->validate([
            'depot_id'       => 'required|integer',
            'product_id'     => 'required|integer',
            'client_id'      => 'nullable|integer',
            'client_name'    => 'nullable|string|max:120',
            'sale_date'      => 'nullable|date',
            'qty'            => 'required|numeric|min:0.001',
            'unit_price'     => 'required|numeric|min:0',
            'currency'       => 'required|string|max:8',

            'delivery_mode'    => 'required|in:ex_depot,delivered',
            'transporter_id'   => 'nullable|integer',
            'truck_no'         => 'nullable|string|max:32',
            'trailer_no'       => 'nullable|string|max:32',
            'waybill_no'       => 'nullable|string|max:64',
            'delivery_notes'   => 'nullable|string',
            'freight_amount'   => 'nullable|numeric|min:0',
            'freight_currency' => 'nullable|string|max:8',
            'driver_name'      => 'nullable|string|max:150',
            'seal_numbers'     => 'nullable|string',
            'temperature'      => 'nullable|numeric|min:-20|max:100',
            'density'          => 'nullable|numeric|min:0|max:2',

            'advance_account_id' => 'nullable|integer',
            'trip_advance'       => 'nullable|numeric|min:0',
            'fuel_advance'       => 'nullable|numeric|min:0',
            'advance_currency'   => 'nullable|string|max:8',
        ]);

        $depotOk = Depot::query()->where('company_id', $cid)->whereKey((int) $data['depot_id'])->exists();
        if (!$depotOk) return back()->withErrors(['depot_id' => 'Invalid depot for this company.'])->withInput();

        $productOk = Product::query()->where('company_id', $cid)->whereKey((int) $data['product_id'])->exists();
        if (!$productOk) return back()->withErrors(['product_id' => 'Invalid product for this company.'])->withInput();

        if (!empty($data['transporter_id'])) {
            $transporterOk = Transporter::query()->where('company_id', $cid)->whereKey((int) $data['transporter_id'])->exists();
            if (!$transporterOk) return back()->withErrors(['transporter_id' => 'Invalid transporter for this company.'])->withInput();
        }

        $qty   = (float) $data['qty'];
        // Check for sufficient stock before allowing edit
        $availableTotal = 0.0;
        $layers = \App\Models\DepotStock::query()
            ->where('company_id', $cid)
            ->where('depot_id', (int) $data['depot_id'])
            ->where('product_id', (int) $data['product_id'])
            ->whereRaw('(qty_on_hand - qty_reserved) > 0')
            ->get();
        foreach ($layers as $l) {
            $availableTotal += max(0, (float) $l->qty_on_hand - (float) $l->qty_reserved);
        }
        if ($availableTotal + 1e-9 < $qty) {
            return back()->withErrors(['qty' => 'Insufficient stock in depot for this product.'])->withInput();
        }

        $unit  = (float) $data['unit_price'];
        $total = round($qty * $unit, 2);

        // Credit limit check (draft sales have no posted AR yet, so check full new amount)
        if (!empty($data['client_id'])) {
            $limitError = $this->checkCreditLimit(
                (int) $data['client_id'],
                $cid,
                $total,
                $data['currency'] ?? 'USD'
            );
            if ($limitError) {
                return back()->withErrors(['client_id' => $limitError])->withInput();
            }
        }

        $sale->depot_id     = (int) $data['depot_id'];
        $sale->product_id   = (int) $data['product_id'];
        $sale->client_id    = $data['client_id'] ? (int) $data['client_id'] : null;
        $sale->client_name  = $data['client_name'] ?? null;
        $sale->sale_date    = $data['sale_date'] ?? $sale->sale_date;
        $sale->qty          = $qty;
        $sale->unit_price   = $unit;
        $sale->currency     = $data['currency'] ?? 'USD';
        $sale->total        = $total;

        $sale->delivery_mode  = $data['delivery_mode'];
        $sale->transporter_id = !empty($data['transporter_id']) ? (int) $data['transporter_id'] : null;

        if ($data['delivery_mode'] === 'delivered') {
            $sale->truck_no         = $data['truck_no'] ?? null;
            $sale->trailer_no       = $data['trailer_no'] ?? null;
            $sale->waybill_no       = $data['waybill_no'] ?? null;
            $sale->delivery_notes   = $data['delivery_notes'] ?? null;
            $hasTransporter         = !empty($data['transporter_id']);
            $sale->freight_amount   = $hasTransporter && !empty($data['freight_amount'])
                                        ? (float) $data['freight_amount'] : null;
            $sale->freight_currency = $hasTransporter
                                        ? ($data['freight_currency'] ?? 'USD') : null;
            $sale->driver_name      = $data['driver_name'] ?? null;
            $sale->seal_numbers     = $data['seal_numbers'] ?? null;
            $sale->temperature      = isset($data['temperature']) ? (float) $data['temperature'] : 20.0;
            $sale->density          = isset($data['density']) && $data['density'] !== '' ? (float) $data['density'] : null;
        } else {
            $sale->truck_no         = null;
            $sale->trailer_no       = null;
            $sale->waybill_no       = null;
            $sale->delivery_notes   = null;
            $sale->freight_amount   = null;
            $sale->freight_currency = null;
            $sale->driver_name      = null;
            $sale->seal_numbers     = null;
            $sale->temperature      = null;
            $sale->density          = null;
        }

        // Advance fields (on delivered mode only)
        if ($data['delivery_mode'] === 'delivered') {
            $sale->advance_account_id = !empty($data['advance_account_id']) ? (int) $data['advance_account_id'] : null;
            $sale->trip_advance       = !empty($data['trip_advance']) ? (float) $data['trip_advance'] : null;
            $sale->fuel_advance       = !empty($data['fuel_advance']) ? (float) $data['fuel_advance'] : null;
            $sale->advance_currency   = $data['advance_currency'] ?? 'USD';
        } else {
            $sale->advance_account_id = null;
            $sale->trip_advance       = null;
            $sale->fuel_advance       = null;
            $sale->advance_currency   = null;
        }

        $sale->updated_by = $u?->id;
        $sale->save();

        // Re-post advances (void existing advance txns for this sale, re-create)
        DB::table('petty_cash_transactions')
            ->where('ref_type', 'sale')
            ->where('ref_id', $sale->id)
            ->whereIn('category', ['trip_advance','fuel_advance'])
            ->delete();
        $this->postDriverAdvances($sale, $data, $cid);

        return redirect()->route('sales.index', ['sale' => $sale->id])
            ->with('status', 'Draft updated.');
    }

    /**
     * Post trip/fuel advances as petty cash expense transactions for a sale.
     * Idempotent when called from update() — caller deletes old rows first.
     */
    private function postDriverAdvances(Sale $sale, array $data, int $cid): void
    {
        $accountId = !empty($data['advance_account_id']) ? (int) $data['advance_account_id'] : null;
        if (!$accountId) return;

        $account = PettyCashAccount::where('id', $accountId)
            ->where('company_id', $cid)
            ->first();
        if (!$account) return;

        $currency = $data['advance_currency'] ?? $account->currency ?? 'USD';
        $driver   = $data['driver_name'] ?? 'driver';
        $saleRef  = $sale->reference ?? "Sale #{$sale->id}";
        $today    = now()->toDateString();

        $advances = [
            'trip_advance' => (float) ($data['trip_advance'] ?? 0),
            'fuel_advance' => (float) ($data['fuel_advance'] ?? 0),
        ];

        foreach ($advances as $category => $amount) {
            if ($amount <= 0) continue;
            $label = $category === 'trip_advance' ? 'Trip advance' : 'Fuel advance';
            PettyCashTransaction::create([
                'company_id'       => $cid,
                'account_id'       => $account->id,
                'type'             => 'expense',
                'amount'           => -$amount,
                'currency'         => $currency,
                'description'      => "{$label} — {$saleRef} ({$driver})",
                'recipient'        => $driver,
                'category'         => $category,
                'transaction_date' => $today,
                'ref_type'         => 'sale',
                'ref_id'           => $sale->id,
                'created_by'       => auth()->id(),
            ]);
        }
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

        try {
            DB::transaction(function () use ($sale, $u, $ledger, $qty) {

                $result = $ledger->issue(
                    [
                        'company_id'    => (int) $sale->company_id,
                        'product_id'    => (int) $sale->product_id,
                        'from_depot_id' => (int) $sale->depot_id,
                        'qty'           => $qty,
                        'batch_id'      => $sale->batch_id ? (int) $sale->batch_id : null,

                        'ref_type'      => 'sale',
                        'ref_id'        => (int) $sale->id,
                        'reference'     => 'sale:' . $sale->id,
                        'notes'         => 'Sale issue',

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

                // Auto-post freight charge + driver advances to transporter ledger (idempotent)
                if ($sale->transporter_id) {
                    $entryDate = $sale->sale_date
                        ? \Carbon\Carbon::parse($sale->sale_date)->format('Y-m-d')
                        : now()->format('Y-m-d');

                    // Freight charge
                    if ((float) $sale->freight_amount > 0) {
                        $freightAlreadyPosted = \App\Models\TransporterLedgerEntry::where('company_id', (int) $sale->company_id)
                            ->where('transporter_id', (int) $sale->transporter_id)
                            ->where('type', 'freight_charge')
                            ->where('ref_type', \App\Models\Sale::class)
                            ->where('ref_id', $sale->id)
                            ->exists();

                        if (!$freightAlreadyPosted) {
                            \App\Models\TransporterLedgerEntry::create([
                                'company_id'     => (int) $sale->company_id,
                                'transporter_id' => (int) $sale->transporter_id,
                                'type'           => 'freight_charge',
                                'sale_id'        => $sale->id,
                                'amount'         => (float) $sale->freight_amount,
                                'currency'       => $sale->freight_currency ?: 'USD',
                                'description'    => 'Freight — Sale ' . $sale->reference,
                                'entry_date'     => $entryDate,
                                'ref_type'       => \App\Models\Sale::class,
                                'ref_id'         => $sale->id,
                                'created_by'     => $u?->id,
                            ]);
                        }
                    }

                    // Driver advances reduce the transporter's net payable (negative amount = advance paid)
                    $advanceCurrency = $sale->advance_currency ?: 'USD';
                    foreach ([
                        'trip_advance' => 'Trip advance',
                        'fuel_advance' => 'Fuel advance',
                    ] as $field => $label) {
                        $advAmt = (float) ($sale->$field ?? 0);
                        if ($advAmt <= 0) continue;

                        $alreadyPosted = \App\Models\TransporterLedgerEntry::where('company_id', (int) $sale->company_id)
                            ->where('transporter_id', (int) $sale->transporter_id)
                            ->where('type', 'advance')
                            ->where('ref_type', \App\Models\Sale::class)
                            ->where('ref_id', $sale->id)
                            ->where('description', 'LIKE', "{$label}%")
                            ->exists();

                        if (!$alreadyPosted) {
                            \App\Models\TransporterLedgerEntry::create([
                                'company_id'     => (int) $sale->company_id,
                                'transporter_id' => (int) $sale->transporter_id,
                                'type'           => 'advance',
                                'sale_id'        => $sale->id,
                                'amount'         => -$advAmt, // negative = reduces payable
                                'currency'       => $advanceCurrency,
                                'description'    => "{$label} — Sale " . $sale->reference . ($sale->driver_name ? ' (' . $sale->driver_name . ')' : ''),
                                'entry_date'     => $entryDate,
                                'ref_type'       => \App\Models\Sale::class,
                                'ref_id'         => $sale->id,
                                'created_by'     => $u?->id,
                            ]);
                        }
                    }
                }

                // Auto-post AR ledger entry + generate invoice if client is linked
                if ($sale->client_id) {
                    ClientLedgerController::postInvoice(
                        (int) $sale->client_id,
                        (int) $sale->company_id,
                        (float) $sale->total,
                        $sale->currency ?? 'USD',
                        \App\Models\Sale::class,
                        $sale->id,
                        'Invoice for sale ' . $sale->reference,
                        $sale->sale_date
                            ? \Carbon\Carbon::parse($sale->sale_date)->format('Y-m-d')
                            : now()->format('Y-m-d')
                    );

                    // Auto-generate invoice document (idempotent: skip if already exists)
                    $alreadyHasInvoice = Invoice::where('sale_id', $sale->id)->exists();
                    if (!$alreadyHasInvoice) {
                        $company = $u?->activeCompany ?? Company::find($sale->company_id);
                        if ($company) {
                            Invoice::createFromSale($sale->load('product'), $company);
                        }
                    }
                }

                AuditLog::record(
                    'posted',
                    "Sale {$sale->reference} posted — {$qty} L, total {$sale->currency} " . number_format((float)$sale->total, 2),
                    $sale,
                    "Sale {$sale->reference}",
                    severity: 'warning',
                    after: [
                        'status'    => 'posted',
                        'qty'       => $qty,
                        'total'     => $sale->total,
                        'currency'  => $sale->currency,
                        'client_id' => $sale->client_id,
                    ],
                    module: 'Sale',
                );
            });

            // Auto-post journal entries — non-blocking, silently skips if CoA not seeded
            try {
                \App\Services\JournalAutoPost::for((int) $sale->company_id)
                    ->postSale(
                        saleId:      $sale->id,
                        reference:   $sale->reference,
                        revenue:     (float) $sale->total,
                        cogs:        (float) ($sale->cogs_total ?? 0),
                        currency:    $sale->currency ?? 'USD',
                        description: 'Sale ' . $sale->reference
                    );
            } catch (\Throwable) {}

        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Insufficient stock')) {
                return back()->withErrors(['qty' => 'Insufficient stock in depot for this product.'])->withInput();
            }
            throw $e;
        }

        return redirect()->route('sales.index', ['sale' => $sale->id])
            ->with('status', "Sale posted. Stock issued. Movement created.");
    }

    public function deliveryNote(Sale $sale)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);
        if ((int)$sale->company_id !== $cid) abort(404);

        $company = Company::firstOrFail();
        $sale->load(['depot', 'product', 'transporter', 'client']);

        // Parse seal numbers: split on newlines/commas, expand ranges
        $sealNumbers = [];
        if ($sale->seal_numbers) {
            $parts = preg_split('/[\n,]+/', $sale->seal_numbers);
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') continue;
                if (preg_match('/^(\d+)\s*[-–]\s*(\d+)$/', $part, $m)) {
                    $from = (int) $m[1];
                    $to   = (int) $m[2];
                    if ($from <= $to && ($to - $from) <= 500) {
                        for ($i = $from; $i <= $to; $i++) {
                            $sealNumbers[] = (string) $i;
                        }
                    }
                } else {
                    $sealNumbers[] = $part;
                }
            }
        }

        return view('sales.delivery-note', compact('sale', 'company', 'sealNumbers'));
    }

    public function confirmPod(Request $request, Sale $sale)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);
        if ((int)$sale->company_id !== $cid) abort(404);

        if ($sale->status !== 'posted') {
            return back()->with('error', 'Only posted sales can have POD confirmed.');
        }

        $data = $request->validate([
            'qty_delivered'   => 'required|numeric|min:0',
            'pod_received_at' => 'required|date',
            'pod_notes'       => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($sale, $u, $data) {
            $qtyDelivered = (float) $data['qty_delivered'];
            $shortfallQty = max(0, (float) $sale->qty - $qtyDelivered);
            $notes        = $data['pod_notes'] ?? '';

            if ($shortfallQty > 0) {
                $allowedLoss = (float) $sale->qty * (((float) ($sale->product?->allowed_loss_pct ?? 0)) / 100);
                $excessLoss  = max(0, $shortfallQty - $allowedLoss);
                if ($excessLoss > 0) {
                    $notes = trim("Shortfall: " . number_format($shortfallQty, 3) . " L (excess loss: " . number_format($excessLoss, 3) . " L). " . $notes);
                }
            }

            $sale->qty_delivered     = $qtyDelivered;
            $sale->pod_received_at   = $data['pod_received_at'];
            $sale->pod_notes         = $notes ?: null;
            $sale->pod_confirmed_by  = $u?->id;
            $sale->status            = 'delivered';
            $sale->save();

            // Generate invoice document at POD (idempotent)
            if ($sale->client_id) {
                $alreadyHasInvoice = Invoice::where('sale_id', $sale->id)->exists();
                if (!$alreadyHasInvoice) {
                    $company = Company::find($sale->company_id);
                    if ($company) {
                        Invoice::createFromSale($sale->load('product'), $company);
                    }
                }
            }

            AuditLog::record(
                'pod_confirmed',
                "POD confirmed for sale {$sale->reference} — {$qtyDelivered} L delivered.",
                $sale,
                "Sale {$sale->reference}",
                severity: 'info',
                after: ['status' => 'delivered', 'qty_delivered' => $qtyDelivered],
                module: 'Sale',
            );
        });

        // Store mailto link in session so the invoice page can offer "Email Client"
        $invoice = Invoice::where('sale_id', $sale->id)->where('type', 'invoice')->latest()->first();
        if ($invoice) {
            session(['pod_invoice_id' => $invoice->id]);
        }

        return redirect()->route('sales.index', ['sale' => $sale->id])
            ->with('status', 'POD confirmed. Sale marked as delivered.');
    }

    public function cancelSale(Request $request, Sale $sale)
    {
        $u   = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);
        if ((int)$sale->company_id !== $cid) abort(404);

        if (!in_array($sale->status, ['draft', 'posted'])) {
            return back()->with('error', 'Only draft or posted sales can be cancelled.');
        }

        DB::transaction(function () use ($sale, $u) {

            if ($sale->status === 'posted' && $sale->inventory_movement_id) {
                // --- Reverse the inventory ISSUE ---
                $original = InventoryMovement::find($sale->inventory_movement_id);

                // Create a return movement to put stock back
                InventoryMovement::create([
                    'company_id'  => $sale->company_id,
                    'type'        => 'return',
                    'product_id'  => $sale->product_id,
                    'depot_id'    => $sale->depot_id,
                    'qty'         => (float) $sale->qty,
                    'unit_cost'   => $original?->unit_cost,
                    'reference'   => 'sale_cancel:' . $sale->id,
                    'notes'       => 'Reversal — sale ' . ($sale->reference ?? $sale->id) . ' cancelled',
                    'ref_type'    => Sale::class,
                    'ref_id'      => $sale->id,
                    'period_id'   => $original?->period_id,
                    'created_by'  => $u?->id,
                ]);

                // Restore depot stock
                DepotStock::where('company_id', $sale->company_id)
                    ->where('depot_id', $sale->depot_id)
                    ->where('product_id', $sale->product_id)
                    ->increment('qty_on_hand', (float) $sale->qty);

                // Restore batch quantities from consumption rows
                $consumptions = InventoryConsumption::where('movement_id', $sale->inventory_movement_id)->get();
                foreach ($consumptions as $c) {
                    Batch::where('id', $c->batch_id)->increment('qty_remaining', (float) $c->qty);
                }

                // Cancel freight transporter ledger entry
                if ($sale->transporter_id && (float) $sale->freight_amount > 0) {
                    TransporterLedgerEntry::where('ref_type', Sale::class)
                        ->where('ref_id', $sale->id)
                        ->where('type', 'freight_charge')
                        ->delete();
                }

                // Reverse the client AR invoice posted at sale posting
                \App\Models\ClientLedgerEntry::where('ref_type', Sale::class)
                    ->where('ref_id', $sale->id)
                    ->delete();

                // Void any invoice documents
                Invoice::where('sale_id', $sale->id)->delete();
            }

            $sale->status     = 'cancelled';
            $sale->updated_by = $u?->id;
            $sale->save();

            AuditLog::record(
                'cancelled',
                "Sale {$sale->reference} cancelled.",
                $sale,
                "Sale {$sale->reference}",
                severity: 'warning',
                after: ['status' => 'cancelled'],
                module: 'Sale',
            );
        });

        return redirect()->route('sales.index', ['sale' => $sale->id])
            ->with('status', 'Sale cancelled. Stock reversed.');
    }

    /**
     * Check whether a sale to this client would exceed their credit limit.
     * Returns an error string if over limit, null if fine or no limit set.
     * Only compares amounts in the same currency as the sale.
     */
    private function checkCreditLimit(int $clientId, int $companyId, float $saleAmount, string $currency): ?string
    {
        $client = Client::where('company_id', $companyId)->find($clientId);
        if (!$client) {
            return null;
        }

        $limit = (float) ($client->credit_limit ?? 0);
        if ($limit <= 0) {
            return null; // No limit set — allow
        }

        // Outstanding AR balance in this currency (positive = client owes us)
        $outstanding = (float) ClientLedgerEntry::where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->where('currency', $currency)
            ->sum('amount');

        if (($outstanding + $saleAmount) > $limit) {
            $sym = match($currency) {
                'USD' => '$', 'EUR' => '€', 'GBP' => '£',
                default => $currency . ' ',
            };
            return sprintf(
                'Credit limit exceeded for %s. Limit: %s%s · Current outstanding: %s%s · This sale: %s%s.',
                $client->name,
                $sym, number_format($limit, 2),
                $sym, number_format($outstanding, 2),
                $sym, number_format($saleAmount, 2)
            );
        }

        return null;
    }
}