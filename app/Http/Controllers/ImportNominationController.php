<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use App\Models\Purchase;
use App\Models\ImportNomination;
use App\Models\ImportTruck;

class ImportNominationController extends Controller
{
    // ── Create nomination ────────────────────────────────────────────────────

    public function store(Request $request, Purchase $purchase)
    {
        $cid = $this->authorise($purchase);

        $data = $request->validate([
            'transporter_id'        => 'nullable|integer',
            'currency'              => 'required|string|max:8',
            'rate_per_1000l'        => 'required|numeric|min:0',
            'allowed_loss_pct'      => 'required|numeric|min:0|max:100',
            'short_charge_rate'     => 'required|numeric|min:0',
            'short_charge_currency' => 'required|string|max:8',
            'advances'              => 'nullable|numeric|min:0',
            'advances_currency'     => 'required|string|max:8',
            'notes'                 => 'nullable|string|max:2000',
        ]);

        if ($purchase->importNomination) {
            $purchase->importNomination->update(array_merge($data, [
                'advances' => $data['advances'] ?? 0,
            ]));
            return back()->with('status', 'Nomination updated.');
        }

        ImportNomination::create(array_merge($data, [
            'company_id'  => $cid,
            'purchase_id' => $purchase->id,
            'advances'    => $data['advances'] ?? 0,
            'created_by'  => auth()->id(),
        ]));

        if ($purchase->status === 'confirmed') {
            $purchase->update(['status' => 'nominated']);
        }

        return back()->with('status', 'Import nomination created. You can now add trucks.');
    }

    // ── Update nomination ────────────────────────────────────────────────────

    public function update(Request $request, Purchase $purchase, ImportNomination $nomination)
    {
        $this->authorise($purchase);
        abort_if((int) $nomination->purchase_id !== $purchase->id, 403);

        $data = $request->validate([
            'transporter_id'        => 'nullable|integer',
            'currency'              => 'required|string|max:8',
            'rate_per_1000l'        => 'required|numeric|min:0',
            'allowed_loss_pct'      => 'required|numeric|min:0|max:100',
            'short_charge_rate'     => 'required|numeric|min:0',
            'short_charge_currency' => 'required|string|max:8',
            'advances'              => 'nullable|numeric|min:0',
            'advances_currency'     => 'required|string|max:8',
            'notes'                 => 'nullable|string|max:2000',
        ]);

        $nomination->update(array_merge($data, [
            'advances' => $data['advances'] ?? 0,
        ]));

        return back()->with('status', 'Nomination updated.');
    }

    // ── Add truck ────────────────────────────────────────────────────────────

    public function addTruck(Request $request, Purchase $purchase, ImportNomination $nomination)
    {
        $cid = $this->authorise($purchase);
        abort_if((int) $nomination->purchase_id !== $purchase->id, 403);

        $data = $request->validate([
            'truck_reg'       => 'nullable|string|max:40',
            'trailer_reg'     => 'nullable|string|max:40',
            'driver_name'     => 'nullable|string|max:150',
            'driver_passport' => 'nullable|string|max:60',
            'driver_license'  => 'nullable|string|max:60',
            'driver_phone'    => 'nullable|string|max:30',
            'capacity'        => 'required|numeric|min:1',
            'notes'           => 'nullable|string|max:1000',
        ]);

        try {
            ImportTruck::create(array_merge($data, [
                'company_id'    => $cid,
                'nomination_id' => $nomination->id,
                'status'        => 'nominated',
                'created_by'    => auth()->id(),
            ]));
        } catch (UniqueConstraintViolationException $e) {
            if (str_contains($e->getMessage(), 'trailer_reg')) {
                return back()
                    ->withInput()
                    ->withErrors(['trailer_reg' => "Trailer registration '{$data['trailer_reg']}' is already added to this nomination."]);
            }
            return back()
                ->withInput()
                ->withErrors(['truck_reg' => "Truck registration '{$data['truck_reg']}' is already added to this nomination."]);
        }

        return back()->with('status', "Truck {$data['truck_reg']} added.");
    }

    // ── Update truck ─────────────────────────────────────────────────────────

    public function updateTruck(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);
        abort_if(in_array($truck->status, ['loaded', 'in_transit', 'border_cleared', 'delivered']), 422,
            'Cannot edit a truck that has already been loaded.');

        $data = $request->validate([
            'truck_reg'       => 'nullable|string|max:40',
            'trailer_reg'     => 'nullable|string|max:40',
            'driver_name'     => 'nullable|string|max:150',
            'driver_passport' => 'nullable|string|max:60',
            'driver_license'  => 'nullable|string|max:60',
            'driver_phone'    => 'nullable|string|max:30',
            'capacity'        => 'required|numeric|min:1',
            'notes'           => 'nullable|string|max:1000',
        ]);

        try {
            $truck->update($data);
        } catch (UniqueConstraintViolationException $e) {
            if (str_contains($e->getMessage(), 'trailer_reg')) {
                return back()
                    ->withInput()
                    ->withErrors(['trailer_reg' => "Trailer registration '{$data['trailer_reg']}' is already used by another truck in this nomination."])
                    ->with('edit_error_truck_id', $truck->id);
            }
            return back()
                ->withInput()
                ->withErrors(['truck_reg' => "Truck registration '{$data['truck_reg']}' is already used by another truck in this nomination."])
                ->with('edit_error_truck_id', $truck->id);
        }

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

        return back()->with('status', "Load recorded: {$data['qty_loaded']} L on {$data['pickup_date']}.");
    }

    // ── Mark loading failed ──────────────────────────────────────────────────

    public function failLoad(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);
        abort_if($truck->status !== 'nominated', 422, 'Only nominated trucks can be marked as load-failed.');

        $truck->update(['status' => 'loading_failed', 'load_notes' => $request->input('load_notes')]);

        return back()->with('status', "Truck {$truck->truck_reg} marked as loading failed.");
    }

    // ── Mark in transit ──────────────────────────────────────────────────────

    public function markInTransit(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);
        abort_if($truck->status !== 'loaded', 422, 'Truck must be loaded before marking in transit.');

        $truck->update(['status' => 'in_transit']);

        return back()->with('status', "Truck {$truck->truck_reg} marked as in transit.");
    }

    // ── Record border clearance ──────────────────────────────────────────────

    public function recordBorder(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if((int) $truck->nomination_id !== $nomination->id, 403);
        abort_if($truck->status !== 'in_transit', 422, 'Truck must be in transit to record border clearance.');

        $data = $request->validate([
            'tr8_number'  => 'nullable|string|max:80',
            't1_number'   => 'nullable|string|max:80',
            'border_date' => 'required|date',
        ]);

        $truck->update(array_merge($data, ['status' => 'border_cleared']));

        return back()->with('status', "Border clearance recorded for {$truck->truck_reg}.");
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

        $qtyLoaded       = (float) $truck->qty_loaded;
        $qtyDelivered    = (float) $data['qty_delivered'];
        $lossPct         = (float) $nomination->allowed_loss_pct / 100;
        $shortfallQty    = max(0, $qtyLoaded - $qtyDelivered);
        $allowedLossQty  = round($qtyLoaded * $lossPct, 3);
        $excessLossQty   = max(0, round($shortfallQty - $allowedLossQty, 3));
        $shortfallCharge = round($excessLossQty * ((float) $nomination->short_charge_rate / 1000), 2);

        $truck->update(array_merge($data, [
            'status'           => 'delivered',
            'shortfall_qty'    => $shortfallQty,
            'allowed_loss_qty' => $allowedLossQty,
            'excess_loss_qty'  => $excessLossQty,
            'shortfall_charge' => $shortfallCharge,
        ]));

        $msg = "Delivery recorded: {$qtyDelivered} L.";
        if ($excessLossQty > 0) {
            $msg .= " Chargeable shortfall: {$excessLossQty} L → {$nomination->short_charge_currency} "
                  . number_format($shortfallCharge, 2) . '.';
        }

        return back()->with('status', $msg);
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
