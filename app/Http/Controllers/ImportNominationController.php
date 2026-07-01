<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Purchase;
use App\Models\ImportNomination;
use App\Models\ImportTruck;
use App\Models\NominationAdvance;
use App\Models\TransporterLedgerEntry;
use App\Http\Controllers\SupplierLedgerController;
use App\Services\DepotChargeAutoPost;
use App\Services\InventoryLedger;
use App\Services\DutyPostingService;

class ImportNominationController extends Controller
{
    // ── Create nomination ────────────────────────────────────────────────────

    public function store(Request $request, Purchase $purchase)
    {
        $cid = $this->authorise($purchase);

        $data = $request->validate([
            'transporter_id'             => 'nullable|integer',
            'destination_depot_id'       => 'nullable|integer',
            'currency'                   => 'required|string|max:8',
            'rate_per_1000l'             => 'required|numeric|min:0',
            'allowed_loss_pct'           => 'required|numeric|min:0|max:100',
            'short_charge_rate'          => 'required|numeric|min:0',
            'short_charge_currency'      => 'required|string|max:8',
            'notes'                      => 'nullable|string|max:2000',
            'default_duty_vendor_type'   => 'nullable|string|max:30',
            'default_duty_vendor_id'     => 'nullable|integer',
            'default_duty_rate_per_1000l'=> 'nullable|numeric|min:0',
            'default_duty_currency'      => 'nullable|string|max:8',
        ]);
        $data['destination_depot_id']  = $data['destination_depot_id'] ?: null;
        $data['default_duty_vendor_id'] = $data['default_duty_vendor_id'] ?: null;

        // Validate default duty vendor ownership if an AP type is selected
        $this->validateDefaultDutyVendor($cid, $data);

        if ($purchase->importNomination) {
            $nom = $purchase->importNomination;

            // Rate lock — once any truck has progressed beyond nominated, rate is frozen
            if ((float) $data['rate_per_1000l'] !== (float) $nom->rate_per_1000l) {
                $progressedTrucks = $nom->trucks()
                    ->whereNotIn('status', ['nominated', 'loading_failed'])
                    ->exists();
                if ($progressedTrucks) {
                    return back()
                        ->withErrors(['rate_per_1000l' => 'Transport rate cannot be changed once trucks have started loading.'])
                        ->withInput();
                }
            }

            // volume_unit is intentionally NOT updated — it's locked at creation time
            // so historical calculations remain correct if the global setting changes
            $nom->update($data);
            return back()->with('status', 'Nomination updated.');
        }

        // Snapshot the company's current volume_unit at creation — immune to future global changes
        $companyVolumeUnit = DB::table('companies')->where('id', $cid)->value('volume_unit') ?? 'L';

        $nom = ImportNomination::create(array_merge($data, [
            'company_id'  => $cid,
            'purchase_id' => $purchase->id,
            'advances'    => $data['advances'] ?? 0,
            'created_by'  => auth()->id(),
            'volume_unit' => $companyVolumeUnit,
        ]));

        if ($purchase->status === 'confirmed') {
            $purchase->update(['status' => 'nominated']);
        }

        $this->syncAdvanceEntry($cid, $nom, $data);

        \App\Models\AuditLog::record(
            'nominated',
            "Import nomination created for purchase {$purchase->reference} (transporter rate: {$data['rate_per_1000l']} {$data['currency']}/1000L).",
            $purchase, "Purchase {$purchase->reference}",
            severity: 'info',
            after: ['rate_per_1000l' => $data['rate_per_1000l'], 'currency' => $data['currency'], 'allowed_loss_pct' => $data['allowed_loss_pct']],
        );

        return back()->with('status', 'Import nomination created. You can now add trucks.');
    }

    // ── Update nomination ────────────────────────────────────────────────────

    public function update(Request $request, Purchase $purchase, ImportNomination $nomination)
    {
        $cid = $this->authorise($purchase);
        abort_if((int) $nomination->purchase_id !== $purchase->id, 403);

        $data = $request->validate([
            'transporter_id'             => 'nullable|integer',
            'destination_depot_id'       => 'nullable|integer',
            'currency'                   => 'required|string|max:8',
            'rate_per_1000l'             => 'required|numeric|min:0',
            'allowed_loss_pct'           => 'required|numeric|min:0|max:100',
            'short_charge_rate'          => 'required|numeric|min:0',
            'short_charge_currency'      => 'required|string|max:8',
            'notes'                      => 'nullable|string|max:2000',
            'default_duty_vendor_type'   => 'nullable|string|max:30',
            'default_duty_vendor_id'     => 'nullable|integer',
            'default_duty_rate_per_1000l'=> 'nullable|numeric|min:0',
            'default_duty_currency'      => 'nullable|string|max:8',
        ]);
        $data['destination_depot_id']   = $data['destination_depot_id'] ?: null;
        $data['default_duty_vendor_id'] = $data['default_duty_vendor_id'] ?: null;

        // Validate default duty vendor ownership if an AP type is selected
        $this->validateDefaultDutyVendor($cid, $data);

        $nomination->update(array_merge($data, [
            'advances' => $data['advances'] ?? 0,
        ]));

        $this->syncAdvanceEntry($cid, $nomination, $data);
        return back()->with('status', 'Nomination updated.');
    }

    // ── Add truck ────────────────────────────────────────────────────────────

    public function addTruck(Request $request, Purchase $purchase, ImportNomination $nomination)
    {
        $cid = $this->authorise($purchase);
        abort_if((int) $nomination->purchase_id !== $purchase->id, 403);

        $validator = validator($request->all(), [
            'truck_reg'       => [
                'nullable', 'string', 'max:40',
                Rule::unique('import_trucks')
                    ->where('nomination_id', $nomination->id),
            ],
            'trailer_reg'     => [
                'nullable', 'string', 'max:40',
                Rule::unique('import_trucks')
                    ->where('nomination_id', $nomination->id),
            ],
            'driver_name'     => 'nullable|string|max:150',
            'driver_passport' => 'nullable|string|max:60',
            'driver_license'  => 'nullable|string|max:60',
            'driver_phone'    => 'nullable|string|max:30',
            'capacity'        => 'required|numeric|min:1',
            'notes'           => 'nullable|string|max:1000',
        ], [
            'truck_reg.unique'   => "Truck registration ':input' is already added to this nomination.",
            'trailer_reg.unique' => "Trailer registration ':input' is already added to this nomination.",
        ]);

        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        $data = $validator->validated();

        // Pre-fill duty defaults from nomination
        $dutyDefaults = [];
        if ($nomination->default_duty_vendor_type) {
            $dutyDefaults = [
                'duty_vendor_type'   => $nomination->default_duty_vendor_type,
                'duty_vendor_id'     => $nomination->default_duty_vendor_id,
                'duty_rate_per_1000l'=> $nomination->default_duty_rate_per_1000l,
                'duty_currency'      => $nomination->default_duty_currency ?: 'USD',
                'duty_status'        => 'pending',
            ];
        }

        $truck = ImportTruck::create(array_merge($data, $dutyDefaults, [
            'company_id'    => $cid,
            'nomination_id' => $nomination->id,
            'status'        => 'nominated',
            'created_by'    => auth()->id(),
        ]));

        \App\Models\AuditLog::record(
            'created',
            "Truck {$data['truck_reg']} added to purchase {$purchase->reference} (capacity: {$data['capacity']}).",
            $truck, "Truck {$data['truck_reg']} · {$purchase->reference}",
            severity: 'info',
            after: ['truck_reg' => $data['truck_reg'], 'capacity' => $data['capacity'], 'driver' => $data['driver_name'] ?? null],
        );

        return back()->with('status', "Truck {$data['truck_reg']} added.");
    }

    // ── Update truck ─────────────────────────────────────────────────────────

    public function updateTruck(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);
        abort_if(in_array($truck->status, ['loaded', 'in_transit', 'border_cleared', 'delivered']), 422,
            'Cannot edit a truck that has already been loaded.');

        $validator = validator($request->all(), [
            'truck_reg'       => [
                'nullable', 'string', 'max:40',
                Rule::unique('import_trucks')
                    ->where('nomination_id', $nomination->id)
                    ->ignore($truck->id),
            ],
            'trailer_reg'     => [
                'nullable', 'string', 'max:40',
                Rule::unique('import_trucks')
                    ->where('nomination_id', $nomination->id)
                    ->ignore($truck->id),
            ],
            'driver_name'                => 'nullable|string|max:150',
            'driver_passport'            => 'nullable|string|max:60',
            'driver_license'             => 'nullable|string|max:60',
            'driver_phone'               => 'nullable|string|max:30',
            'capacity'                   => 'required|numeric|min:1',
            'notes'                      => 'nullable|string|max:1000',
            'short_charge_rate_override' => 'nullable|numeric|min:0',
        ], [
            'truck_reg.unique'   => "Truck registration ':input' is already used by another truck in this nomination.",
            'trailer_reg.unique' => "Trailer registration ':input' is already used by another truck in this nomination.",
        ]);

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator)
                ->with('edit_error_truck_id', $truck->id);
        }

        $validated = $validator->validated();
        // Store null explicitly when the field is cleared (empty string → null)
        $validated['short_charge_rate_override'] = $validated['short_charge_rate_override'] !== '' && $validated['short_charge_rate_override'] !== null
            ? (float) $validated['short_charge_rate_override']
            : null;

        $truck->update($validated);

        return back()->with('status', 'Truck updated.');
    }

    // ── Record load ──────────────────────────────────────────────────────────

    public function recordLoad(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);
        abort_if($truck->status !== 'nominated', 422, 'Truck must be in nominated status to record loading.');

        $data = $request->validate([
            'qty_loaded'      => 'required|numeric|min:1',
            'pickup_date'     => 'required|date',
            'pickup_terminal' => 'nullable|string|max:200',
            'load_notes'      => 'nullable|string|max:1000',
        ]);

        $truck->update(array_merge($data, ['status' => 'loaded']));

        \App\Models\AuditLog::record(
            'updated',
            "Truck {$truck->truck_reg} loaded: {$data['qty_loaded']} L at {$data['pickup_terminal']} on {$data['pickup_date']} · {$purchase->reference}.",
            $truck, "Truck {$truck->truck_reg} · {$purchase->reference}",
            severity: 'info',
            after: ['status' => 'loaded', 'qty_loaded' => $data['qty_loaded'], 'pickup_date' => $data['pickup_date'], 'pickup_terminal' => $data['pickup_terminal'] ?? null],
        );

        return back()->with('status', "Load recorded: {$data['qty_loaded']} L on {$data['pickup_date']}.");
    }

    // ── Mark loading failed ──────────────────────────────────────────────────

    public function failLoad(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);
        abort_if($truck->status !== 'nominated', 422, 'Only nominated trucks can be marked as load-failed.');

        $truck->update(['status' => 'loading_failed', 'load_notes' => $request->input('load_notes')]);

        \App\Models\AuditLog::record(
            'updated',
            "Truck {$truck->truck_reg} loading failed · {$purchase->reference}. Reason: " . ($request->input('load_notes') ?: 'none provided') . ".",
            $truck, "Truck {$truck->truck_reg} · {$purchase->reference}",
            severity: 'warning',
            after: ['status' => 'loading_failed', 'notes' => $request->input('load_notes')],
        );

        return back()->with('status', "Truck {$truck->truck_reg} marked as loading failed.");
    }

    // ── Mark in transit ──────────────────────────────────────────────────────

    public function markInTransit(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);

        if ($truck->status !== 'loaded') {
            return back()->with('error', "Truck {$truck->truck_reg} must be in Loaded status before marking in transit (currently: {$truck->statusLabel()}).");
        }

        $truck->update(['status' => 'in_transit', 'in_transit_at' => now()]);

        \App\Models\AuditLog::record(
            'updated',
            "Truck {$truck->truck_reg} marked in transit · {$purchase->reference}.",
            $truck, "Truck {$truck->truck_reg} · {$purchase->reference}",
            severity: 'info',
            after: ['status' => 'in_transit', 'in_transit_at' => now()->toDateTimeString()],
        );

        return back()->with('status', "Truck {$truck->truck_reg} marked as in transit.");
    }

    // ── Mark arrived at border ───────────────────────────────────────────────

    public function markAtBorder(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);

        if ($truck->status !== 'in_transit') {
            return back()->with('error', "Truck {$truck->truck_reg} must be In Transit before marking arrived at border (currently: {$truck->statusLabel()}).");
        }

        $data = $request->validate([
            'border_post'          => 'required|string|max:120',
            'arrived_at_border_at' => 'nullable|date',
        ]);

        $truck->update([
            'status'               => 'at_border',
            'border_post'          => $data['border_post'],
            'arrived_at_border_at' => $data['arrived_at_border_at'] ? now()->parse($data['arrived_at_border_at']) : now(),
        ]);

        \App\Models\AuditLog::record(
            'updated',
            "Truck {$truck->truck_reg} arrived at border post '{$data['border_post']}' · {$purchase->reference}.",
            $truck, "Truck {$truck->truck_reg} · {$purchase->reference}",
            severity: 'info',
            after: ['status' => 'at_border', 'border_post' => $data['border_post'], 'arrived_at_border_at' => now()->toDateTimeString()],
        );

        return back()->with('status', "Truck {$truck->truck_reg} marked as arrived at {$data['border_post']}.");
    }

    // ── Record border clearance ──────────────────────────────────────────────

    public function recordBorder(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $cid = $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);

        if ($truck->status !== 'at_border') {
            return back()->with('error', "Truck {$truck->truck_reg} must be At Border before recording clearance (currently: {$truck->statusLabel()}).");
        }

        $data = $request->validate([
            'tr8_number'             => 'nullable|string|max:80',
            't1_number'              => 'nullable|string|max:80',
            'border_date'            => 'required|date',
            'border_post'            => 'nullable|string|max:120',
            'waive_duty'             => 'nullable|boolean',
            'duty_vendor_type'       => ['nullable', Rule::in(['customs_authority', 'supplier', 'depot', 'transporter', 'self', ''])],
            'duty_vendor_id'         => 'nullable|integer',
            'duty_rate_per_1000l'    => 'nullable|numeric|min:0',
            'duty_qty'               => 'nullable|numeric|min:0',
            'duty_currency'          => 'nullable|string|max:8',
            'duty_notes'             => 'nullable|string|max:500',
            'other_border_charges'   => 'nullable|numeric|min:0',
            'other_border_currency'  => 'nullable|string|max:8',
            'other_border_notes'     => 'nullable|string|max:500',
            'tr8_doc'                => 'nullable|file|max:20480|mimes:pdf,jpg,jpeg,png',
            't1_doc'                 => 'nullable|file|max:20480|mimes:pdf,jpg,jpeg,png',
            'other_doc'              => 'nullable|file|max:20480|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $waiveDuty = (bool) ($data['waive_duty'] ?? false);

        // If duty is waived, skip all AP vendor validation and post as waived
        if (! $waiveDuty) {
            // Validate duty vendor ownership when an AP type is selected
            $submittedType = $data['duty_vendor_type'] ?? null;
            $submittedId   = (int) ($data['duty_vendor_id'] ?? 0);
            $apTypes       = ['customs_authority', 'supplier', 'depot', 'transporter'];

            if ($submittedType && in_array($submittedType, $apTypes, true)) {
                if (! $submittedId) {
                    return back()->withErrors(['duty_vendor_id' => 'A vendor must be selected for the chosen duty type.'])->withInput();
                }

                $vendorExists = match ($submittedType) {
                    'customs_authority' => DB::table('duty_vendors')
                        ->where('id', $submittedId)->where('company_id', $cid)->exists(),
                    'supplier'          => DB::table('suppliers')
                        ->where('id', $submittedId)->where('company_id', $cid)->exists(),
                    'depot'             => DB::table('depots')
                        ->where('id', $submittedId)->where('company_id', $cid)->exists(),
                    'transporter'       => DB::table('transporters')
                        ->where('id', $submittedId)->where('company_id', $cid)->exists(),
                    default             => false,
                };

                if (! $vendorExists) {
                    return back()->withErrors(['duty_vendor_id' => 'Selected duty vendor not found or does not belong to this company.'])->withInput();
                }
            }
        }

        // Compute duty amount
        $dutyRate = $waiveDuty ? 0.0 : (float) ($data['duty_rate_per_1000l'] ?? $truck->duty_rate_per_1000l ?? 0);
        $dutyQty  = $waiveDuty ? 0.0 : (float) ($data['duty_qty'] ?? $truck->qty_loaded ?? 0);
        $dutyAmt  = $dutyRate > 0 && $dutyQty > 0 ? round($dutyRate * $dutyQty / $this->dutyDivisor($purchase), 4) : null;

        $otherChargesFields = [
            'other_border_charges'  => ($data['other_border_charges'] ?? null) ? (float) $data['other_border_charges'] : null,
            'other_border_currency' => ($data['other_border_currency'] ?? null) ?: ($truck->other_border_currency ?: 'USD'),
            'other_border_notes'    => $data['other_border_notes'] ?? null,
        ];

        if ($waiveDuty) {
            $truck->update(array_merge($otherChargesFields, [
                'status'           => 'border_cleared',
                'border_date'      => $data['border_date'],
                'border_post'      => $data['border_post'] ?? null,
                'tr8_number'       => $data['tr8_number'] ?? null,
                't1_number'        => $data['t1_number'] ?? null,
                'duty_status'      => 'waived',
                'duty_vendor_type' => null,
                'duty_vendor_id'   => null,
                'duty_amount'      => null,
                'duty_notes'       => $data['duty_notes'] ?? null,
            ]));
        } else {
            $truck->update(array_merge($data, $otherChargesFields, [
                'status'              => 'border_cleared',
                'duty_vendor_id'      => ($data['duty_vendor_id'] ?? null) ?: ($truck->duty_vendor_id ?: null),
                'duty_vendor_type'    => ($data['duty_vendor_type'] ?? null) ?: ($truck->duty_vendor_type ?: null),
                'duty_rate_per_1000l' => $dutyRate ?: null,
                'duty_qty'            => $dutyQty ?: null,
                'duty_amount'         => $dutyAmt,
                'duty_currency'       => ($data['duty_currency'] ?? null) ?: ($truck->duty_currency ?: 'USD'),
                'duty_status'         => $truck->duty_status ?? 'pending',
            ]));
        }

        // Auto-post duty now that we have border date (skip if waived)
        $truck->refresh();
        $dutyMsg = null;
        if (! $waiveDuty && $truck->duty_vendor_type && ($truck->duty_amount ?? 0) > 0) {
            try {
                $dutyMsg = DutyPostingService::postForTruck($truck, (int) auth()->id());
            } catch (\Throwable $e) {
                return back()->with('status', "Border cleared. Duty auto-post failed: {$e->getMessage()}");
            }
        }

        // Attach uploaded border documents
        \App\Http\Controllers\DocumentController::attachFromRequest($request, $truck, 'tr8_doc', 'tr8');
        \App\Http\Controllers\DocumentController::attachFromRequest($request, $truck, 't1_doc', 't1');
        \App\Http\Controllers\DocumentController::attachFromRequest($request, $truck, 'other_doc', 'customs');

        \App\Models\AuditLog::record(
            'updated',
            "Border clearance recorded for truck {$truck->truck_reg} · {$purchase->reference}. " .
                ($data['tr8_number'] ?? null ? "TR8: {$data['tr8_number']}. " : '') .
                ($data['t1_number'] ?? null ? "T1: {$data['t1_number']}. " : '') .
                ($waiveDuty ? 'Duty waived.' : ($dutyAmt ? "Duty: {$truck->duty_currency} {$dutyAmt}." : 'No duty.')),
            $truck, "Truck {$truck->truck_reg} · {$purchase->reference}",
            severity: 'info',
            after: ['status' => 'border_cleared', 'tr8_number' => $data['tr8_number'] ?? null, 't1_number' => $data['t1_number'] ?? null, 'duty_amount' => $dutyAmt, 'duty_waived' => $waiveDuty],
        );

        $msg = "Border clearance recorded for {$truck->truck_reg}.";
        if ($dutyMsg) {
            $msg .= " {$dutyMsg}";
        }

        session()->forget('error');
        return back()->with('status', $msg);
    }

    // ── Record delivery ──────────────────────────────────────────────────────

    public function recordDelivery(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $cid = $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);
        abort_if($truck->status !== 'border_cleared', 422, 'Truck must be border-cleared before recording delivery.');

        $data = $request->validate([
            'depot_id'       => 'required|integer',
            'qty_delivered'  => 'required|numeric|min:0',
            'delivery_date'  => 'required|date',
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        $depotOk = DB::table('depots')
            ->where('company_id', $cid)
            ->where('id', (int) $data['depot_id'])
            ->where('is_active', true)
            ->exists();
        if (!$depotOk) {
            return back()->with('error', 'Invalid depot selected.');
        }

        // Use the nomination's own volume_unit (snapshotted at creation) — immune to global changes
        $volumeUnit  = $nomination->volume_unit ?? 'L';
        $rateDivisor = 1; // Rate is always per unit (per L or per M³)

        $qtyLoaded       = (float) $truck->qty_loaded;
        $qtyDelivered    = (float) $data['qty_delivered'];
        $lossPct         = (float) $nomination->allowed_loss_pct / 100;
        $shortfallQty    = max(0, $qtyLoaded - $qtyDelivered);
        $allowedLossQty  = round($qtyLoaded * $lossPct, 3);
        $excessLossQty   = max(0, round($shortfallQty - $allowedLossQty, 3));
        $effectiveShortRate = (float) ($truck->short_charge_rate_override ?? $nomination->short_charge_rate);
        $shortfallCharge = round($excessLossQty * ($effectiveShortRate / $rateDivisor), 2);

        $truck->update(array_merge($data, [
            'status'           => 'delivered',
            'shortfall_qty'    => $shortfallQty,
            'allowed_loss_qty' => $allowedLossQty,
            'excess_loss_qty'  => $excessLossQty,
            'shortfall_charge' => $shortfallCharge,
        ]));

        // Compute landed unit cost: purchase price + duty (if assessed) + freight
        $freightAmt = $nomination->transporter_id
            ? round($qtyLoaded * (float) $nomination->rate_per_1000l, 2)
            : 0.0;
        $dutyAmt    = ($truck->duty_status && $truck->duty_status !== 'waived')
            ? (float) ($truck->duty_amount ?? 0)
            : 0.0;
        $landedPerUnit = $qtyLoaded > 0 ? round(($dutyAmt + $freightAmt) / $qtyLoaded, 6) : 0.0;
        $unitCost   = round((float) $purchase->unit_price + $landedPerUnit, 6);

        // Post inventory receipt into the depot (idempotent per truck)
        if ($qtyDelivered > 0 && $purchase->batch_id && $purchase->product_id) {
            $ledger = app(InventoryLedger::class);
            $ledger->receipt(
                [
                    'company_id'  => $cid,
                    'product_id'  => (int) $purchase->product_id,
                    'to_depot_id' => (int) $data['depot_id'],
                    'batch_id'    => (int) $purchase->batch_id,
                    'qty'         => $qtyDelivered,
                    'unit_cost'   => $unitCost,
                    'total_cost'  => round($qtyDelivered * $unitCost, 2),
                    'ref_type'    => 'import_truck',
                    'ref_id'      => (int) $truck->id,
                    'reference'   => 'import-delivery:' . $truck->id,
                    'notes'       => "Import delivery — truck {$truck->truck_reg} ({$qtyDelivered} {$volumeUnit})",
                    'created_by'  => auth()->id(),
                ],
                ['type' => 'receipt', 'ref_type' => 'import_truck', 'ref_id' => (int) $truck->id]
            );

            // Auto-apply depot shrinkage immediately on receipt
            $depot        = \App\Models\Depot::find((int) $data['depot_id']);
            $shrinkagePct = (float) ($depot?->default_shrinkage_pct ?? 0);
            if ($shrinkagePct > 0) {
                $shrinkageQty = round($qtyDelivered * $shrinkagePct / 100, 4);
                if ($shrinkageQty > 0.0001) {
                    $ledger->adjustment([
                        'company_id'  => $cid,
                        'product_id'  => (int) $purchase->product_id,
                        'depot_id'    => (int) $data['depot_id'],
                        'batch_id'    => (int) $purchase->batch_id,
                        'reason_type' => 'depot_shrinkage',
                        'qty'         => $shrinkageQty,
                        'ref_type'    => 'import_truck',
                        'ref_id'      => (int) $truck->id,
                        'reference'   => 'shrinkage:truck:' . $truck->id,
                        'notes'       => "Auto depot shrinkage ({$shrinkagePct}%) — truck {$truck->truck_reg}",
                        'created_by'  => auth()->id(),
                    ], ['type' => 'adjustment', 'reference' => 'shrinkage:truck:' . $truck->id]);
                }
            }
        }

        // Resolve ledger currency once — transporter default_currency takes precedence over nomination currency
        $ledgerCurrency = $nomination->currency ?? 'USD';
        if ($nomination->transporter_id) {
            $ledgerCurrency = DB::table('transporters')
                ->where('id', $nomination->transporter_id)
                ->value('default_currency') ?? $ledgerCurrency;
        }

        // Post ledger entries for freight earned + short charge (idempotent per truck)
        if ($nomination->transporter_id) {

            // $freightAmt already computed above for landed cost

            if ($freightAmt > 0 && !TransporterLedgerEntry::where('ref_type', ImportTruck::class)
                    ->where('ref_id', $truck->id)->where('type', 'freight_charge')->exists()) {
                TransporterLedgerEntry::create([
                    'company_id'     => $cid,
                    'transporter_id' => $nomination->transporter_id,
                    'type'           => 'freight_charge',
                    'amount'         => $freightAmt,
                    'currency'       => $ledgerCurrency,
                    'description'    => "Freight for truck {$truck->truck_reg} — {$qtyLoaded} {$volumeUnit} loaded",
                    'entry_date'     => $data['delivery_date'],
                    'ref_type'       => ImportTruck::class,
                    'ref_id'         => $truck->id,
                    'created_by'     => auth()->id(),
                ]);
            }

            if ($shortfallCharge > 0 && !TransporterLedgerEntry::where('ref_type', ImportTruck::class)
                    ->where('ref_id', $truck->id)->where('type', 'short_charge')->exists()) {
                TransporterLedgerEntry::create([
                    'company_id'     => $cid,
                    'transporter_id' => $nomination->transporter_id,
                    'type'           => 'short_charge',
                    'amount'         => -$shortfallCharge,
                    'currency'       => $ledgerCurrency,
                    'description'    => "Shortfall charge for truck {$truck->truck_reg} — {$excessLossQty} L excess loss",
                    'entry_date'     => $data['delivery_date'],
                    'ref_type'       => ImportTruck::class,
                    'ref_id'         => $truck->id,
                    'created_by'     => auth()->id(),
                ]);
            }
        }

        if ($qtyDelivered > 0) {
            try {
                \App\Services\JournalAutoPost::for($cid)
                    ->postImportDelivery(
                        truckId:     $truck->id,
                        reference:   'IMP-TRK-' . $truck->id,
                        amount:      round($qtyDelivered * (float) $purchase->unit_price, 2),
                        currency:    $purchase->currency ?? 'USD',
                        description: "Import delivery truck {$truck->truck_reg} — {$qtyDelivered} {$volumeUnit} @ {$purchase->unit_price} {$purchase->currency}",
                    );
            } catch (\Throwable) {}
        }

        // Auto-post freight as a batch cost (idempotent per truck)
        if ($purchase->batch_id && $freightAmt > 0) {
            $freightCostExists = DB::table('batch_costs')
                ->where('batch_id', $purchase->batch_id)
                ->where('truck_id', $truck->id)
                ->where('category', 'freight')
                ->exists();
            if (!$freightCostExists) {
                DB::table('batch_costs')->insert([
                    'batch_id'           => $purchase->batch_id,
                    'purchase_id'        => $purchase->id,
                    'nomination_id'      => $nomination->id,
                    'truck_id'           => $truck->id,
                    'company_id'         => $cid,
                    'category'           => 'freight',
                    'description'        => "Freight — truck {$truck->truck_reg} ({$qtyLoaded} {$volumeUnit})",
                    'amount'             => $freightAmt,
                    'currency'           => $ledgerCurrency,
                    'exchange_rate'      => 1,
                    'amount_base'        => $freightAmt,
                    'entry_date'         => $data['delivery_date'],
                    'is_included_in_cost'=> false,
                    'auto_posted'        => true,
                    'created_by'         => auth()->id(),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }
        }

        // NOTE: Shortfall charge is NOT a batch cost — it is deducted from the transporter's payout
        // (already posted to transporter ledger above as a negative short_charge entry).

        // Auto-post all depot charge configs (storage, offloading, duty, customs, etc.)
        $depotChargesPosted = DepotChargeAutoPost::postForDelivery(
            truck:           $truck,
            depotId:         (int) $data['depot_id'],
            qtyDeliveredL:   $qtyDelivered,
            deliveryDate:    $data['delivery_date'],
            purchase:        $purchase,
            nomination:      $nomination,
            cid:             $cid,
            createdBy:       (int) auth()->id(),
        );

        $msg = "Delivery recorded: {$qtyDelivered} L.";
        if ($excessLossQty > 0) {
            $msg .= " Shortfall: {$nomination->short_charge_currency} "
                  . number_format($shortfallCharge, 2) . ' deducted from transporter.';
        }
        if ($freightAmt > 0) {
            $msg .= " Freight posted: " . number_format($freightAmt, 2) . ".";
        }
        if (!empty($depotChargesPosted)) {
            $msg .= " Depot charges: " . implode('; ', $depotChargesPosted) . ".";
        }

        \App\Models\AuditLog::record(
            'received',
            "Delivery recorded for truck {$truck->truck_reg} · {$purchase->reference}: {$qtyDelivered} L delivered" .
                ($excessLossQty > 0 ? ", shortfall charge {$nomination->short_charge_currency} " . number_format($shortfallCharge, 2) : '') . ".",
            $truck, "Truck {$truck->truck_reg} · {$purchase->reference}",
            severity: $excessLossQty > 0 ? 'warning' : 'info',
            after: ['status' => 'delivered', 'qty_delivered' => $qtyDelivered, 'shortfall_qty' => $shortfallQty, 'excess_loss_qty' => $excessLossQty, 'shortfall_charge' => $shortfallCharge],
        );

        return back()->with('status', $msg);
    }

    // ── Quick load + deliver (skip intermediate stages) ──────────────────────
    // Accepts a truck in 'nominated' status and records the full load→deliver
    // pipeline in one step. Useful for catching up records after the fact.

    public function quickLoadDeliver(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $cid = $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);
        abort_if($truck->status !== 'nominated', 422, 'Quick post is only available for trucks that have not started loading yet.');

        $data = $request->validate([
            'qty_loaded'     => 'required|numeric|min:1',
            'qty_delivered'  => 'required|numeric|min:0',
            'depot_id'       => 'required|integer',
            'date'           => 'required|date',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $depotOk = DB::table('depots')
            ->where('company_id', $cid)
            ->where('id', (int) $data['depot_id'])
            ->where('is_active', true)
            ->exists();
        if (!$depotOk) {
            return back()->with('error', 'Invalid depot selected.');
        }

        // Use the nomination's own volume_unit (snapshotted at creation) — immune to global changes
        $volumeUnit      = $nomination->volume_unit ?? 'L';
        $rateDivisor     = 1; // Rate is always per unit (per L or per M³)
        $qtyLoaded       = (float) $data['qty_loaded'];
        $qtyDelivered    = (float) $data['qty_delivered'];
        $lossPct         = (float) $nomination->allowed_loss_pct / 100;
        $shortfallQty    = max(0, $qtyLoaded - $qtyDelivered);
        $allowedLossQty  = round($qtyLoaded * $lossPct, 3);
        $excessLossQty   = max(0, round($shortfallQty - $allowedLossQty, 3));
        $effectiveShortRate = (float) ($truck->short_charge_rate_override ?? $nomination->short_charge_rate);
        $shortfallCharge = round($excessLossQty * ($effectiveShortRate / $rateDivisor), 2);

        $truck->update([
            'status'           => 'delivered',
            'qty_loaded'       => $qtyLoaded,
            'pickup_date'      => $data['date'],
            'qty_delivered'    => $qtyDelivered,
            'delivery_date'    => $data['date'],
            'depot_id'         => $data['depot_id'],
            'delivery_notes'   => $data['notes'],
            'shortfall_qty'    => $shortfallQty,
            'allowed_loss_qty' => $allowedLossQty,
            'excess_loss_qty'  => $excessLossQty,
            'shortfall_charge' => $shortfallCharge,
        ]);

        // Compute landed unit cost: purchase price + duty (if assessed) + freight
        $freightAmt = $nomination->transporter_id
            ? round($qtyLoaded * (float) $nomination->rate_per_1000l, 2)
            : 0.0;
        $dutyAmt    = ($truck->duty_status && $truck->duty_status !== 'waived')
            ? (float) ($truck->duty_amount ?? 0)
            : 0.0;
        $landedPerUnit = $qtyLoaded > 0 ? round(($dutyAmt + $freightAmt) / $qtyLoaded, 6) : 0.0;
        $unitCost   = round((float) $purchase->unit_price + $landedPerUnit, 6);

        // Post inventory receipt into the depot (idempotent per truck)
        if ($qtyDelivered > 0 && $purchase->batch_id && $purchase->product_id) {
            $ledger = app(InventoryLedger::class);
            $ledger->receipt(
                [
                    'company_id'  => $cid,
                    'product_id'  => (int) $purchase->product_id,
                    'to_depot_id' => (int) $data['depot_id'],
                    'batch_id'    => (int) $purchase->batch_id,
                    'qty'         => $qtyDelivered,
                    'unit_cost'   => $unitCost,
                    'total_cost'  => round($qtyDelivered * $unitCost, 2),
                    'ref_type'    => 'import_truck',
                    'ref_id'      => (int) $truck->id,
                    'reference'   => 'import-delivery:' . $truck->id,
                    'notes'       => "Import delivery — truck {$truck->truck_reg} ({$qtyDelivered} {$volumeUnit})",
                    'created_by'  => auth()->id(),
                ],
                ['type' => 'receipt', 'ref_type' => 'import_truck', 'ref_id' => (int) $truck->id]
            );
        }

        // Resolve ledger currency once — transporter default_currency takes precedence over nomination currency
        $ledgerCurrency = $nomination->currency ?? 'USD';
        if ($nomination->transporter_id) {
            $ledgerCurrency = DB::table('transporters')
                ->where('id', $nomination->transporter_id)
                ->value('default_currency') ?? $ledgerCurrency;
        }

        // Post transporter entries (freight + shortfall charge)
        if ($nomination->transporter_id) {
            // $freightAmt already computed above for landed cost
            if ($freightAmt > 0 && !TransporterLedgerEntry::where('ref_type', ImportTruck::class)->where('ref_id', $truck->id)->where('type', 'freight_charge')->exists()) {
                TransporterLedgerEntry::create([
                    'company_id'     => $cid, 'transporter_id' => $nomination->transporter_id,
                    'type'           => 'freight_charge', 'amount' => $freightAmt,
                    'currency'       => $ledgerCurrency,
                    'description'    => "Freight — truck {$truck->truck_reg} ({$qtyLoaded} {$volumeUnit})",
                    'entry_date'     => $data['date'], 'ref_type' => ImportTruck::class,
                    'ref_id'         => $truck->id, 'created_by' => auth()->id(),
                ]);
            }
            if ($shortfallCharge > 0 && !TransporterLedgerEntry::where('ref_type', ImportTruck::class)->where('ref_id', $truck->id)->where('type', 'short_charge')->exists()) {
                TransporterLedgerEntry::create([
                    'company_id'     => $cid, 'transporter_id' => $nomination->transporter_id,
                    'type'           => 'short_charge', 'amount' => -$shortfallCharge,
                    'currency'       => $ledgerCurrency,
                    'description'    => "Shortfall charge — {$truck->truck_reg} ({$excessLossQty} L excess)",
                    'entry_date'     => $data['date'], 'ref_type' => ImportTruck::class,
                    'ref_id'         => $truck->id, 'created_by' => auth()->id(),
                ]);
            }
        } else { $freightAmt = 0; }

        // Auto-post freight batch cost
        if ($purchase->batch_id && $freightAmt > 0 && !DB::table('batch_costs')->where('batch_id', $purchase->batch_id)->where('truck_id', $truck->id)->where('category', 'freight')->exists()) {
            DB::table('batch_costs')->insert([
                'batch_id' => $purchase->batch_id, 'purchase_id' => $purchase->id,
                'nomination_id' => $nomination->id, 'truck_id' => $truck->id,
                'company_id' => $cid, 'category' => 'freight',
                'description' => "Freight — truck {$truck->truck_reg} ({$qtyLoaded} {$volumeUnit})",
                'amount' => $freightAmt, 'currency' => $ledgerCurrency,
                'exchange_rate' => 1, 'amount_base' => $freightAmt,
                'entry_date' => $data['date'], 'is_included_in_cost' => false,
                'auto_posted' => true, 'created_by' => auth()->id(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Auto-post all depot charge configs (storage, offloading, duty, customs, etc.)
        $depotChargesPosted = DepotChargeAutoPost::postForDelivery(
            truck:           $truck,
            depotId:         (int) $data['depot_id'],
            qtyDeliveredL:   $qtyDelivered,
            deliveryDate:    $data['date'],
            purchase:        $purchase,
            nomination:      $nomination,
            cid:             $cid,
            createdBy:       (int) auth()->id(),
        );

        $msg = "Quick delivery posted: {$qtyLoaded} L loaded, {$qtyDelivered} L delivered.";
        if ($excessLossQty > 0) {
            $msg .= " Shortfall: {$nomination->short_charge_currency} " . number_format($shortfallCharge, 2) . " deducted from transporter.";
        }
        if (!empty($depotChargesPosted)) {
            $msg .= " Depot charges posted: " . implode('; ', $depotChargesPosted) . ".";
        }

        return back()->with('status', $msg);
    }

    // ── Bulk quick post (nominated → delivered in one step, multiple trucks) ──

    public function bulkQuickPost(Request $request, Purchase $purchase, ImportNomination $nomination)
    {
        $cid = $this->authorise($purchase);
        abort_if((int) $nomination->purchase_id !== $purchase->id, 403);

        $rows = $request->input('trucks', []);
        if (empty($rows)) {
            return back()->with('error', 'No truck data submitted.');
        }

        $volumeUnit  = $nomination->volume_unit ?? 'L';
        $rateDivisor = 1;
        $lossPct     = (float) $nomination->allowed_loss_pct / 100;

        $posted = 0;
        $errors = [];

        foreach ($rows as $truckId => $row) {
            if (empty($row['include'])) continue;

            $truck = ImportTruck::where('nomination_id', $nomination->id)
                ->where('id', (int) $truckId)
                ->where('status', 'nominated')
                ->first();

            if (!$truck) {
                $errors[] = "Truck #$truckId not found or already past nominated status.";
                continue;
            }

            $qtyLoaded    = (float) ($row['qty_loaded']    ?? 0);
            $qtyDelivered = (float) ($row['qty_delivered']  ?? 0);
            $depotId      = (int)   ($row['depot_id']       ?? 0);
            $date         = $row['date'] ?? null;

            if ($qtyLoaded < 1 || $qtyDelivered < 0 || !$depotId || !$date) {
                $errors[] = "Truck {$truck->truck_reg}: missing required fields (qty loaded, qty delivered, date, depot).";
                continue;
            }

            $depotOk = DB::table('depots')
                ->where('company_id', $cid)
                ->where('id', $depotId)
                ->where('is_active', true)
                ->exists();
            if (!$depotOk) {
                $errors[] = "Truck {$truck->truck_reg}: invalid depot selected.";
                continue;
            }

            $shortfallQty    = max(0, $qtyLoaded - $qtyDelivered);
            $allowedLossQty  = round($qtyLoaded * $lossPct, 3);
            $excessLossQty   = max(0, round($shortfallQty - $allowedLossQty, 3));
            $effectiveShortRate = (float) ($truck->short_charge_rate_override ?? $nomination->short_charge_rate);
        $shortfallCharge = round($excessLossQty * ($effectiveShortRate / $rateDivisor), 2);

            $truck->update([
                'status'           => 'delivered',
                'qty_loaded'       => $qtyLoaded,
                'pickup_date'      => $date,
                'qty_delivered'    => $qtyDelivered,
                'delivery_date'    => $date,
                'depot_id'         => $depotId,
                'delivery_notes'   => $row['notes'] ?? null,
                'shortfall_qty'    => $shortfallQty,
                'allowed_loss_qty' => $allowedLossQty,
                'excess_loss_qty'  => $excessLossQty,
                'shortfall_charge' => $shortfallCharge,
            ]);

            // Compute landed unit cost: purchase price + duty (if assessed) + freight
            $freightAmt = $nomination->transporter_id
                ? round($qtyLoaded * (float) $nomination->rate_per_1000l, 2)
                : 0.0;
            $dutyAmt    = ($truck->duty_status && $truck->duty_status !== 'waived')
                ? (float) ($truck->duty_amount ?? 0)
                : 0.0;
            $landedPerUnit = $qtyLoaded > 0 ? round(($dutyAmt + $freightAmt) / $qtyLoaded, 6) : 0.0;
            $unitCost   = round((float) $purchase->unit_price + $landedPerUnit, 6);

            // Post inventory receipt into the depot (idempotent per truck)
            if ($qtyDelivered > 0 && $purchase->batch_id && $purchase->product_id) {
                $ledger = app(InventoryLedger::class);
                $ledger->receipt(
                    [
                        'company_id'  => $cid,
                        'product_id'  => (int) $purchase->product_id,
                        'to_depot_id' => $depotId,
                        'batch_id'    => (int) $purchase->batch_id,
                        'qty'         => $qtyDelivered,
                        'unit_cost'   => $unitCost,
                        'total_cost'  => round($qtyDelivered * $unitCost, 2),
                        'ref_type'    => 'import_truck',
                        'ref_id'      => (int) $truck->id,
                        'reference'   => 'import-delivery:' . $truck->id,
                        'notes'       => "Import delivery — truck {$truck->truck_reg} ({$qtyDelivered} {$volumeUnit})",
                        'created_by'  => auth()->id(),
                    ],
                    ['type' => 'receipt', 'ref_type' => 'import_truck', 'ref_id' => (int) $truck->id]
                );
            }

            $ledgerCurrency = $nomination->currency ?? 'USD';
            if ($nomination->transporter_id) {
                $ledgerCurrency = DB::table('transporters')
                    ->where('id', $nomination->transporter_id)
                    ->value('default_currency') ?? $ledgerCurrency;
                // $freightAmt already computed above for landed cost
                if ($freightAmt > 0 && !TransporterLedgerEntry::where('ref_type', ImportTruck::class)->where('ref_id', $truck->id)->where('type', 'freight_charge')->exists()) {
                    TransporterLedgerEntry::create([
                        'company_id'     => $cid, 'transporter_id' => $nomination->transporter_id,
                        'type'           => 'freight_charge', 'amount' => $freightAmt,
                        'currency'       => $ledgerCurrency,
                        'description'    => "Freight — truck {$truck->truck_reg} ({$qtyLoaded} {$volumeUnit})",
                        'entry_date'     => $date, 'ref_type' => ImportTruck::class,
                        'ref_id'         => $truck->id, 'created_by' => auth()->id(),
                    ]);
                }
                if ($shortfallCharge > 0 && !TransporterLedgerEntry::where('ref_type', ImportTruck::class)->where('ref_id', $truck->id)->where('type', 'short_charge')->exists()) {
                    TransporterLedgerEntry::create([
                        'company_id'     => $cid, 'transporter_id' => $nomination->transporter_id,
                        'type'           => 'short_charge', 'amount' => -$shortfallCharge,
                        'currency'       => $ledgerCurrency,
                        'description'    => "Shortfall charge — {$truck->truck_reg} ({$excessLossQty} L excess)",
                        'entry_date'     => $date, 'ref_type' => ImportTruck::class,
                        'ref_id'         => $truck->id, 'created_by' => auth()->id(),
                    ]);
                }
            }

            if ($purchase->batch_id && $freightAmt > 0 && !DB::table('batch_costs')->where('batch_id', $purchase->batch_id)->where('truck_id', $truck->id)->where('category', 'freight')->exists()) {
                DB::table('batch_costs')->insert([
                    'batch_id'             => $purchase->batch_id, 'purchase_id'    => $purchase->id,
                    'nomination_id'        => $nomination->id,     'truck_id'        => $truck->id,
                    'company_id'           => $cid,                'category'        => 'freight',
                    'description'          => "Freight — truck {$truck->truck_reg} ({$qtyLoaded} {$volumeUnit})",
                    'amount'               => $freightAmt,         'currency'        => $ledgerCurrency,
                    'exchange_rate'        => 1,                   'amount_base'     => $freightAmt,
                    'entry_date'           => $date,               'is_included_in_cost' => false,
                    'auto_posted'          => true,                'created_by'      => auth()->id(),
                    'created_at'           => now(),               'updated_at'      => now(),
                ]);
            }

            DepotChargeAutoPost::postForDelivery(
                truck:         $truck,
                depotId:       $depotId,
                qtyDeliveredL: $qtyDelivered,
                deliveryDate:  $date,
                purchase:      $purchase,
                nomination:    $nomination,
                cid:           $cid,
                createdBy:     (int) auth()->id(),
            );

            $posted++;
        }

        $msg = "{$posted} truck(s) quick-posted successfully.";
        if (!empty($errors)) {
            $msg .= ' Issues: ' . implode('; ', $errors);
        }

        return back()->with($errors && $posted === 0 ? 'error' : 'status', $msg);
    }

    // ── Bulk mark in transit ─────────────────────────────────────────────────

    public function bulkMarkInTransit(Request $request, Purchase $purchase, ImportNomination $nomination)
    {
        $this->authorise($purchase);
        abort_if((int) $nomination->purchase_id !== $purchase->id, 403);

        $ids = array_filter(array_map('intval', (array) $request->input('truck_ids', [])));
        if (empty($ids)) {
            return back()->with('error', 'No trucks selected.');
        }

        $updated = ImportTruck::where('nomination_id', $nomination->id)
            ->whereIn('id', $ids)
            ->where('status', 'loaded')
            ->update(['status' => 'in_transit', 'updated_at' => now()]);

        return back()->with('status', "{$updated} truck(s) marked as in transit.");
    }

    // ── Bulk mark border cleared ─────────────────────────────────────────────

    public function bulkMarkBorderCleared(Request $request, Purchase $purchase, ImportNomination $nomination)
    {
        $this->authorise($purchase);
        abort_if((int) $nomination->purchase_id !== $purchase->id, 403);

        $ids = array_filter(array_map('intval', (array) $request->input('truck_ids', [])));
        if (empty($ids)) {
            return back()->with('error', 'No trucks selected.');
        }

        $trucks = ImportTruck::where('nomination_id', $nomination->id)
            ->whereIn('id', $ids)
            ->where('status', 'at_border')
            ->get();

        $now      = now();
        $updated  = 0;
        $dutyMsgs = [];

        foreach ($trucks as $truck) {
            $truck->update([
                'status'           => 'border_cleared',
                'border_cleared_at' => $now,
            ]);
            $updated++;

            // Auto-post duty using nomination defaults if truck has no explicit duty setup
            if (! $truck->duty_vendor_type && $nomination->default_duty_vendor_type) {
                $truck->update([
                    'duty_vendor_type'    => $nomination->default_duty_vendor_type,
                    'duty_vendor_id'      => $nomination->default_duty_vendor_id,
                    'duty_rate_per_1000l' => $truck->duty_rate_per_1000l ?? $nomination->default_duty_rate_per_1000l,
                    'duty_currency'       => $truck->duty_currency ?? $nomination->default_duty_currency ?? 'USD',
                ]);
                $truck->refresh();
            }

            // Compute duty amount if not yet set
            if (($truck->duty_amount ?? 0) <= 0) {
                $rate = (float) ($truck->duty_rate_per_1000l ?? 0);
                $qty  = (float) ($truck->duty_qty ?? $truck->qty_loaded ?? 0);
                if ($rate > 0 && $qty > 0) {
                    $truck->update(['duty_amount' => round($rate * $qty / $this->dutyDivisor($purchase), 4)]);
                    $truck->refresh();
                }
            }

            if ($truck->duty_vendor_type && ($truck->duty_amount ?? 0) > 0) {
                try {
                    $msg = DutyPostingService::postForTruck($truck, (int) auth()->id());
                    if ($msg) {
                        $dutyMsgs[] = $msg;
                    }
                } catch (\Throwable $e) {
                    $dutyMsgs[] = "Duty auto-post failed for {$truck->truck_reg}: {$e->getMessage()}";
                }
            }
        }

        $statusMsg = "{$updated} truck(s) marked as border cleared.";
        if ($dutyMsgs) {
            $statusMsg .= ' ' . implode(' ', $dutyMsgs);
        }

        return back()->with('status', $statusMsg);
    }

    // ── Download truck CSV template ──────────────────────────────────────────

    public function truckTemplate(Purchase $purchase, ImportNomination $nomination)
    {
        $this->authorise($purchase);
        abort_if((int) $nomination->purchase_id !== $purchase->id, 403);

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Truck Reg', 'Trailer Reg', 'Driver Name', 'Driver Passport', 'Driver License', 'Driver Phone', 'Capacity (L)']);
            fputcsv($out, ['ABC-001', 'TRL-001', 'John Doe',  'AB123456', 'DL-001', '+26377000001', '40000']);
            fputcsv($out, ['ABC-002', 'TRL-002', 'Jane Smith','CD789012', 'DL-002', '+26377000002', '38000']);
            fclose($out);
        }, 'trucks-template.csv', ['Content-Type' => 'text/csv']);
    }

    // ── Bulk import trucks from JSON rows ─────────────────────────────────────

    public function importTrucks(Request $request, Purchase $purchase, ImportNomination $nomination)
    {
        $cid = $this->authorise($purchase);
        abort_if((int) $nomination->purchase_id !== $purchase->id, 403);

        $rows = $request->input('rows', []);
        if (!is_array($rows) || count($rows) === 0) {
            return response()->json(['error' => 'No rows provided.'], 422);
        }

        $committed = 0;
        $skipped   = 0;
        $errors    = [];
        $validRows = [];

        // Pre-load truck_regs already saved for this nomination (case-insensitive)
        $existingRegs = ImportTruck::where('nomination_id', $nomination->id)
            ->pluck('truck_reg')
            ->map(fn($r) => strtolower(trim($r)))
            ->flip()
            ->all();

        // Pre-load trailer_regs already saved for this nomination (non-null, case-insensitive)
        $existingTrailerRegs = ImportTruck::where('nomination_id', $nomination->id)
            ->whereNotNull('trailer_reg')
            ->where('trailer_reg', '!=', '')
            ->pluck('trailer_reg')
            ->map(fn($r) => strtolower(trim($r)))
            ->flip()
            ->all();

        $seenInBatch        = []; // track truck_regs encountered within this upload
        $seenTrailersInBatch = []; // track trailer_regs encountered within this upload

        // ── Pass 1: validate every row, collect valid ones ──────────────────
        foreach ($rows as $i => $row) {
            $truckReg   = substr(trim((string) ($row['truck_reg']   ?? '')), 0, 40);
            $driverName = substr(trim((string) ($row['driver_name'] ?? '')), 0, 150);
            $rawCap     = $row['capacity'] ?? '';
            $capacity   = is_numeric($rawCap) ? (float) $rawCap : 0;

            $rowErrors = [];
            if ($truckReg === '')   $rowErrors[] = 'Truck Reg required';
            if ($driverName === '') $rowErrors[] = 'Driver Name required';
            if ($capacity <= 0)    $rowErrors[] = 'Capacity must be > 0';

            if (!empty($rowErrors)) {
                $skipped++;
                $errors[] = ['row' => $i + 2, 'messages' => $rowErrors];
                continue;
            }

            $regKey = strtolower($truckReg);

            if (isset($existingRegs[$regKey])) {
                $skipped++;
                $errors[] = ['row' => $i + 2, 'messages' => ["Truck Reg '{$truckReg}' already exists in this nomination"]];
                continue;
            }

            if (isset($seenInBatch[$regKey])) {
                $skipped++;
                $errors[] = ['row' => $i + 2, 'messages' => ["Truck Reg '{$truckReg}' is duplicated in this upload"]];
                continue;
            }

            $seenInBatch[$regKey] = true;

            $trailerReg    = substr(trim((string) ($row['trailer_reg'] ?? '')), 0, 40) ?: null;
            $trailerRegKey = $trailerReg !== null ? strtolower($trailerReg) : null;

            if ($trailerRegKey !== null) {
                if (isset($existingTrailerRegs[$trailerRegKey])) {
                    $skipped++;
                    $errors[] = ['row' => $i + 2, 'messages' => ["Trailer Reg '{$trailerReg}' already exists in this nomination"]];
                    unset($seenInBatch[$regKey]);
                    continue;
                }

                if (isset($seenTrailersInBatch[$trailerRegKey])) {
                    $skipped++;
                    $errors[] = ['row' => $i + 2, 'messages' => ["Trailer Reg '{$trailerReg}' is duplicated in this upload"]];
                    unset($seenInBatch[$regKey]);
                    continue;
                }

                $seenTrailersInBatch[$trailerRegKey] = true;
            }

            $validRows[] = [
                'company_id'      => $cid,
                'nomination_id'   => $nomination->id,
                'truck_reg'       => $truckReg,
                'trailer_reg'     => $trailerReg,
                'driver_name'     => $driverName,
                'driver_passport' => substr(trim((string) ($row['driver_passport'] ?? '')), 0, 60)  ?: null,
                'driver_license'  => substr(trim((string) ($row['driver_license']  ?? '')), 0, 60)  ?: null,
                'driver_phone'    => substr(trim((string) ($row['driver_phone']    ?? '')), 0, 30)  ?: null,
                'capacity'        => $capacity,
                'status'          => 'nominated',
                'created_by'      => auth()->id(),
            ];
        }

        // ── Pass 2: insert only validated rows in a single transaction ───────
        $importedIds = [];
        DB::transaction(function () use ($validRows, &$committed, &$importedIds) {
            foreach ($validRows as $data) {
                $truck = ImportTruck::create($data);
                $importedIds[] = $truck->id;
                $committed++;
            }
        });

        return response()->json(['committed' => $committed, 'skipped' => $skipped, 'errors' => $errors, 'importedIds' => $importedIds]);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Validate that default_duty_vendor_id belongs to the active company
     * when an AP duty vendor type is selected in a nomination.
     * Aborts with 422 if validation fails.
     */
    private function validateDefaultDutyVendor(int $cid, array $data): void
    {
        $vendorType = $data['default_duty_vendor_type'] ?? null;
        $vendorId   = (int) ($data['default_duty_vendor_id'] ?? 0);
        $apTypes    = ['customs_authority', 'supplier', 'depot', 'transporter'];

        if (! $vendorType || ! in_array($vendorType, $apTypes, true)) {
            return;
        }

        if ($vendorId <= 0) {
            abort(422, "A vendor must be selected for duty type '{$vendorType}'.");
        }

        $exists = match ($vendorType) {
            'customs_authority' => DB::table('duty_vendors')
                ->where('id', $vendorId)->where('company_id', $cid)->exists(),
            'supplier'          => DB::table('suppliers')
                ->where('id', $vendorId)->where('company_id', $cid)->exists(),
            'depot'             => DB::table('depots')
                ->where('id', $vendorId)->where('company_id', $cid)->exists(),
            'transporter'       => DB::table('transporters')
                ->where('id', $vendorId)->where('company_id', $cid)->exists(),
            default             => false,
        };

        if (! $exists) {
            abort(422, "Selected duty vendor (#{$vendorId}, type: {$vendorType}) not found or does not belong to this company.");
        }
    }

    /**
     * Sync the advance ledger entry for a nomination.
     * Updates in-place if entry already exists (non-destructive — preserves audit trail).
     * Removes the entry if advances drop to zero or transporter is unset.
     */
    private function syncAdvanceEntry(int $cid, ImportNomination $nom, array $data): void
    {
        $tid      = (int) ($data['transporter_id'] ?? 0);
        $advances = (float) ($data['advances'] ?? 0);

        $existing = TransporterLedgerEntry::where('company_id', $cid)
            ->where('ref_type', ImportNomination::class)
            ->where('ref_id', $nom->id)
            ->where('type', 'advance')
            ->first();

        // Remove entry if transporter was cleared or advances dropped to zero
        if (!$tid || $advances <= 0) {
            $existing?->delete();
            return;
        }

        // Always use the transporter's default currency — keeps ledger single-currency
        $ledgerCurrency = DB::table('transporters')->where('id', $tid)->value('default_currency') ?? 'USD';

        if ($existing) {
            // Update in-place — preserve created_at and audit trail
            $existing->update([
                'transporter_id' => $tid,
                'amount'         => -$advances,
                'currency'       => $ledgerCurrency,
            ]);
        } else {
            TransporterLedgerEntry::create([
                'company_id'     => $cid,
                'transporter_id' => $tid,
                'type'           => 'advance',
                'amount'         => -$advances,
                'currency'       => $ledgerCurrency,
                'description'    => "Advance for import nomination (Purchase #{$nom->purchase_id})",
                'entry_date'     => now()->toDateString(),
                'ref_type'       => ImportNomination::class,
                'ref_id'         => $nom->id,
                'created_by'     => auth()->id(),
            ]);
        }
    }

    // ── Retroactive duty posting ──────────────────────────────────────────────

    public function postDuty(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $cid = $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);
        abort_if(! in_array($truck->status, ['border_cleared', 'delivered'], true), 422, 'Truck must be border-cleared or delivered to post duty retroactively.');
        abort_if($truck->duty_status === 'posted', 422, 'Duty already posted for this truck.');

        $waive = (bool) $request->input('waive_duty');

        if ($waive) {
            $truck->update([
                'duty_status'    => 'waived',
                'duty_vendor_type'  => null,
                'duty_vendor_id'    => null,
                'duty_amount'       => null,
            ]);
            return back()->with('status', "Duty waived for {$truck->truck_reg}.");
        }

        $data = $request->validate([
            'duty_vendor_type'    => 'nullable|string|max:80',
            'duty_vendor_id'      => 'nullable|integer',
            'duty_rate_per_1000l' => 'nullable|numeric|min:0',
            'duty_qty'            => 'nullable|numeric|min:0',
            'duty_currency'       => 'nullable|string|max:8',
            'duty_notes'          => 'nullable|string|max:500',
        ]);

        $vendorType = $data['duty_vendor_type'] ?: null;
        $vendorId   = !empty($data['duty_vendor_id']) ? (int) $data['duty_vendor_id'] : null;
        $rate       = isset($data['duty_rate_per_1000l']) ? (float) $data['duty_rate_per_1000l'] : null;
        $qty        = isset($data['duty_qty']) && (float) $data['duty_qty'] > 0
                        ? (float) $data['duty_qty']
                        : (float) ($truck->qty_delivered ?? $truck->qty_loaded ?? 0);
        $currency   = $data['duty_currency'] ?: 'USD';
        $amount     = ($rate !== null && $qty > 0) ? round($rate * $qty / $this->dutyDivisor($purchase), 4) : 0;

        if ($amount <= 0 && !$vendorType) {
            return back()->withErrors(['duty_rate_per_1000l' => 'Please enter a rate or waive duty.']);
        }

        $truck->update(array_filter([
            'duty_vendor_type'    => $vendorType,
            'duty_vendor_id'      => $vendorId,
            'duty_rate_per_1000l' => $rate,
            'duty_qty'            => $qty,
            'duty_currency'       => $currency,
            'duty_amount'         => $amount > 0 ? $amount : null,
            'duty_notes'          => $data['duty_notes'] ?? null,
            'duty_status'         => null,
            'border_date'         => $truck->border_date ?? $truck->delivery_date ?? now()->toDateString(),
        ], fn($v) => $v !== null));

        $truck->refresh();

        try {
            $msg = DutyPostingService::postForTruck($truck, (int) auth()->id());
        } catch (\Throwable $e) {
            return back()->with('error', "Duty fields saved but posting failed: {$e->getMessage()}");
        }

        \App\Models\AuditLog::record(
            'updated',
            "Retroactive duty posted for truck {$truck->truck_reg} · {$purchase->reference}. " .
                ($amount > 0 ? "{$currency} " . number_format($amount, 2) . "." : ''),
            $truck, "Truck {$truck->truck_reg} · {$purchase->reference}",
            severity: 'info',
            after: ['duty_status' => 'posted', 'duty_amount' => $amount, 'duty_currency' => $currency],
        );

        return back()->with('status', $msg ?? "Duty posted for {$truck->truck_reg}.");
    }

    // ── Record advance payment ────────────────────────────────────────────────

    public function storeAdvance(Request $request, Purchase $purchase, ImportNomination $nomination)
    {
        $cid = $this->authorise($purchase);
        abort_if((int) $nomination->purchase_id !== $purchase->id, 403);
        abort_if(! $nomination->transporter_id, 422, 'Assign a transporter to the nomination before recording advances.');

        $data = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'currency'     => 'required|string|max:8',
            'advance_date' => 'required|date',
            'note'         => 'nullable|string|max:500',
        ]);

        $advance = NominationAdvance::create([
            'company_id'     => $cid,
            'nomination_id'  => $nomination->id,
            'transporter_id' => $nomination->transporter_id,
            'amount'         => $data['amount'],
            'currency'       => $data['currency'],
            'advance_date'   => $data['advance_date'],
            'note'           => $data['note'] ?? null,
            'created_by'     => auth()->id(),
        ]);

        // Post to transporter ledger
        TransporterLedgerEntry::create([
            'company_id'     => $cid,
            'transporter_id' => $nomination->transporter_id,
            'type'           => 'advance',
            'amount'         => -(float) $data['amount'],
            'currency'       => $data['currency'],
            'description'    => 'Advance payment' . ($data['note'] ? ": {$data['note']}" : '') . " · {$purchase->reference}",
            'entry_date'     => $data['advance_date'],
            'ref_type'       => NominationAdvance::class,
            'ref_id'         => $advance->id,
            'created_by'     => auth()->id(),
        ]);

        \App\Models\AuditLog::record(
            'created',
            "Advance of {$data['currency']} {$data['amount']} recorded for {$purchase->reference} (nomination #{$nomination->id}).",
            $advance, "Advance · {$purchase->reference}",
            severity: 'info',
            after: ['amount' => $data['amount'], 'currency' => $data['currency'], 'date' => $data['advance_date']],
        );

        return back()->with('status', "Advance of {$data['currency']} " . number_format($data['amount'], 2) . " recorded.");
    }

    // ── Void advance payment ──────────────────────────────────────────────────

    public function voidAdvance(Request $request, Purchase $purchase, ImportNomination $nomination, NominationAdvance $advance)
    {
        $cid = $this->authorise($purchase);
        abort_if((int) $nomination->purchase_id !== $purchase->id, 403);
        abort_if((int) $advance->nomination_id !== $nomination->id, 403);
        abort_if($advance->isVoided(), 422, 'This advance has already been voided.');

        // Only manager / accountant / admin / owner can void
        $user = auth()->user();
        $canVoid = $user->hasRole('owner') || $user->hasRole('admin')
                || $user->hasRole('manager') || $user->hasRole('accountant');
        abort_if(! $canVoid, 403, 'Only managers and above can void advance entries.');

        $advance->update([
            'voided_at' => now(),
            'voided_by' => auth()->id(),
        ]);

        // Reverse the ledger entry
        TransporterLedgerEntry::where('ref_type', NominationAdvance::class)
            ->where('ref_id', $advance->id)
            ->where('type', 'advance')
            ->delete();

        \App\Models\AuditLog::record(
            'deleted',
            "Advance of {$advance->currency} {$advance->amount} voided for {$purchase->reference}.",
            $advance, "Advance · {$purchase->reference}",
            severity: 'warning',
            before: ['amount' => $advance->amount, 'currency' => $advance->currency, 'date' => $advance->advance_date],
        );

        return back()->with('status', 'Advance voided and removed from transporter ledger.');
    }

    /**
     * Duty rate divisor based on company volume unit.
     * L  → rate is per 1000 L  → divide qty by 1000
     * M3 → rate is per M³      → divide qty by 1 (1 M³ ≈ 1000 L, same real value)
     */
    private function dutyDivisor(Purchase $purchase): int
    {
        $unit = $purchase->company?->volume_unit ?? 'L';
        return $unit === 'M3' ? 1 : 1000;
    }

    /**
     * Verify the current user owns this purchase's company.
     * Returns the company ID for use in queries.
     */
    private function authorise(Purchase $purchase): int
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $purchase->company_id !== $cid, 403);
        abort_if($purchase->type !== 'import', 422, 'Import logistics only applies to import purchases.');
        return $cid;
    }
}
