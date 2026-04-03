<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Purchase;
use App\Models\ImportNomination;
use App\Models\ImportTruck;
use App\Models\Transporter;

class ImportNominationController extends Controller
{
    // ── Create or update nomination ──────────────────────────────────────────

    public function store(Request $request, Purchase $purchase)
    {
        $this->authorise($purchase);

        $data = $request->validate([
            'transporter_id'       => 'nullable|integer',
            'currency'             => 'required|string|max:8',
            'rate_per_1000l'       => 'required|numeric|min:0',
            'allowed_loss_pct'     => 'required|numeric|min:0|max:100',
            'short_charge_rate'    => 'required|numeric|min:0',
            'short_charge_currency'=> 'required|string|max:8',
            'advances'             => 'nullable|numeric|min:0',
            'advances_currency'    => 'required|string|max:8',
            'notes'                => 'nullable|string|max:2000',
        ]);

        $cid = session('active_company_id');

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

        return back()->with('status', 'Import nomination created. You can now add trucks.');
    }

    // ── Update nomination (edit) ─────────────────────────────────────────────

    public function update(Request $request, Purchase $purchase, ImportNomination $nomination)
    {
        $this->authorise($purchase);
        abort_if($nomination->purchase_id !== $purchase->id, 403);

        $data = $request->validate([
            'transporter_id'       => 'nullable|integer',
            'currency'             => 'required|string|max:8',
            'rate_per_1000l'       => 'required|numeric|min:0',
            'allowed_loss_pct'     => 'required|numeric|min:0|max:100',
            'short_charge_rate'    => 'required|numeric|min:0',
            'short_charge_currency'=> 'required|string|max:8',
            'advances'             => 'nullable|numeric|min:0',
            'advances_currency'    => 'required|string|max:8',
            'notes'                => 'nullable|string|max:2000',
        ]);

        $nomination->update(array_merge($data, [
            'advances' => $data['advances'] ?? 0,
        ]));

        return back()->with('status', 'Nomination updated.');
    }

    // ── Add truck ────────────────────────────────────────────────────────────

    public function addTruck(Request $request, Purchase $purchase, ImportNomination $nomination)
    {
        $this->authorise($purchase);
        abort_if($nomination->purchase_id !== $purchase->id, 403);

        $data = $request->validate([
            'truck_reg'      => 'nullable|string|max:40',
            'trailer_reg'    => 'nullable|string|max:40',
            'driver_name'    => 'nullable|string|max:150',
            'driver_passport'=> 'nullable|string|max:60',
            'driver_license' => 'nullable|string|max:60',
            'driver_phone'   => 'nullable|string|max:30',
            'capacity'       => 'required|numeric|min:1',
            'notes'          => 'nullable|string|max:1000',
        ]);

        ImportTruck::create(array_merge($data, [
            'company_id'    => session('active_company_id'),
            'nomination_id' => $nomination->id,
            'status'        => 'nominated',
            'created_by'    => auth()->id(),
        ]));

        return back()->with('status', "Truck {$data['truck_reg']} added.");
    }

    // ── Update truck ─────────────────────────────────────────────────────────

    public function updateTruck(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if($truck->nomination_id !== $nomination->id, 403);
        abort_if(in_array($truck->status, ['loaded', 'in_transit', 'border_cleared', 'delivered']), 422,
            'Cannot edit a truck that has already been loaded.');

        $data = $request->validate([
            'truck_reg'      => 'nullable|string|max:40',
            'trailer_reg'    => 'nullable|string|max:40',
            'driver_name'    => 'nullable|string|max:150',
            'driver_passport'=> 'nullable|string|max:60',
            'driver_license' => 'nullable|string|max:60',
            'driver_phone'   => 'nullable|string|max:30',
            'capacity'       => 'required|numeric|min:1',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $truck->update($data);
        return back()->with('status', 'Truck updated.');
    }

    // ── Record load ──────────────────────────────────────────────────────────

    public function recordLoad(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if($truck->nomination_id !== $nomination->id, 403);
        abort_if($truck->status !== 'nominated', 422, 'Truck must be in nominated status to record loading.');

        $data = $request->validate([
            'qty_loaded'       => 'required|numeric|min:1',
            'pickup_date'      => 'required|date',
            'pickup_terminal'  => 'nullable|string|max:200',
            'load_notes'       => 'nullable|string|max:1000',
        ]);

        $truck->update(array_merge($data, ['status' => 'loaded']));

        return back()->with('status', "Load recorded: {$data['qty_loaded']} L on {$data['pickup_date']}.");
    }

    // ── Mark loading failed ──────────────────────────────────────────────────

    public function failLoad(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if($truck->nomination_id !== $nomination->id, 403);
        abort_if($truck->status !== 'nominated', 422, 'Only nominated trucks can be marked as load-failed.');

        $truck->update(['status' => 'loading_failed', 'load_notes' => $request->input('load_notes')]);

        return back()->with('status', "Truck {$truck->truck_reg} marked as loading failed.");
    }

    // ── Mark in transit ──────────────────────────────────────────────────────

    public function markInTransit(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if($truck->nomination_id !== $nomination->id, 403);
        abort_if($truck->status !== 'loaded', 422, 'Truck must be loaded before marking in transit.');

        $truck->update(['status' => 'in_transit']);

        return back()->with('status', "Truck {$truck->truck_reg} marked as in transit.");
    }

    // ── Record border clearance ───────────────────────────────────────────────

    public function recordBorder(Request $request, Purchase $purchase, ImportNomination $nomination, ImportTruck $truck)
    {
        $this->authorise($purchase);
        abort_if($truck->nomination_id !== $nomination->id, 403);
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
        $this->authorise($purchase);
        abort_if($truck->nomination_id !== $nomination->id, 403);
        abort_if($truck->status !== 'border_cleared', 422, 'Truck must be border-cleared before recording delivery.');

        $data = $request->validate([
            'depot_id'       => 'required|integer',
            'qty_delivered'  => 'required|numeric|min:0',
            'delivery_date'  => 'required|date',
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        // Validate depot belongs to company
        $depotOk = DB::table('depots')
            ->where('company_id', session('active_company_id'))
            ->where('id', (int) $data['depot_id'])
            ->where('is_active', true)
            ->exists();
        if (!$depotOk) {
            return back()->with('error', 'Invalid depot selected.');
        }

        $qtyLoaded    = (float) $truck->qty_loaded;
        $qtyDelivered = (float) $data['qty_delivered'];
        $lossPct      = (float) $nomination->allowed_loss_pct / 100;

        $shortfallQty   = max(0, $qtyLoaded - $qtyDelivered);
        $allowedLossQty = round($qtyLoaded * $lossPct, 3);
        $excessLossQty  = max(0, round($shortfallQty - $allowedLossQty, 3));
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
            $msg .= " Chargeable shortfall: {$excessLossQty} L → {$nomination->short_charge_currency} " . number_format($shortfallCharge, 2) . '.';
        }

        return back()->with('status', $msg);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function authorise(Purchase $purchase): void
    {
        abort_if($purchase->company_id !== session('active_company_id'), 403);
        abort_if($purchase->type !== 'import', 422, 'Import logistics only applies to import purchases.');
    }
}
