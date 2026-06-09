<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Depot;
use App\Models\DepotChargeConfig;

class DepotChargeConfigController extends Controller
{
    private function authoriseDepot(Depot $depot): int
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $depot->company_id !== $cid, 403);
        abort_if($depot->is_system, 404);
        return $cid;
    }

    public function store(Request $request, Depot $depot)
    {
        $cid = $this->authoriseDepot($depot);

        $data = $request->validate([
            'category'      => 'required|in:storage,offloading,duty,customs,other',
            'name'          => 'required|string|max:200',
            'rate'          => 'required|numeric|min:0',
            'rate_unit'     => 'required|in:per_m3_per_month,per_m3,per_trip,lump_sum',
            'currency'      => 'required|string|max:8',
            'receipt_rule'  => 'nullable|in:include_receipt_month,exclude_receipt_month,prorate_receipt_month,exclude_first_30_days',
            'dispatch_rule' => 'nullable|in:include_dispatch_month,exclude_dispatch_month',
            'paid_by_type'  => 'nullable|in:self,depot,customs_authority,transporter,other',
            'paid_by_id'    => 'nullable|integer',
            'paid_by_name'  => 'nullable|string|max:200',
            'effective_from'=> 'required|date',
            'effective_to'  => 'nullable|date|after_or_equal:effective_from',
            'notes'         => 'nullable|string|max:1000',
        ]);

        DepotChargeConfig::create(array_merge($data, [
            'company_id' => $cid,
            'depot_id'   => $depot->id,
            'is_active'  => true,
            'created_by' => auth()->id(),
        ]));

        return redirect()->route('depots.show', $depot)
            ->with('status', 'Charge config "' . $data['name'] . '" added.');
    }

    public function update(Request $request, Depot $depot, DepotChargeConfig $config)
    {
        $cid = $this->authoriseDepot($depot);
        abort_if((int) $config->depot_id !== $depot->id, 403);

        $data = $request->validate([
            'category'      => 'required|in:storage,offloading,duty,customs,other',
            'name'          => 'required|string|max:200',
            'rate'          => 'required|numeric|min:0',
            'rate_unit'     => 'required|in:per_m3_per_month,per_m3,per_trip,lump_sum',
            'currency'      => 'required|string|max:8',
            'receipt_rule'  => 'nullable|in:include_receipt_month,exclude_receipt_month,prorate_receipt_month,exclude_first_30_days',
            'dispatch_rule' => 'nullable|in:include_dispatch_month,exclude_dispatch_month',
            'paid_by_type'  => 'nullable|in:self,depot,customs_authority,transporter,other',
            'paid_by_id'    => 'nullable|integer',
            'paid_by_name'  => 'nullable|string|max:200',
            'effective_from'=> 'required|date',
            'effective_to'  => 'nullable|date|after_or_equal:effective_from',
            'notes'         => 'nullable|string|max:1000',
            'is_active'     => 'nullable|boolean',
        ]);

        $config->update($data);

        return redirect()->route('depots.show', $depot)
            ->with('status', 'Charge config "' . $config->name . '" updated.');
    }

    public function destroy(Depot $depot, DepotChargeConfig $config)
    {
        $cid = $this->authoriseDepot($depot);
        abort_if((int) $config->depot_id !== $depot->id, 403);

        $config->delete();

        return redirect()->route('depots.show', $depot)
            ->with('status', 'Charge config deleted.');
    }

    public function toggleActive(Depot $depot, DepotChargeConfig $config)
    {
        $this->authoriseDepot($depot);
        abort_if((int) $config->depot_id !== $depot->id, 403);

        $config->update(['is_active' => !$config->is_active]);

        return redirect()->route('depots.show', $depot)
            ->with('status', $config->is_active ? 'Config activated.' : 'Config deactivated.');
    }
}
