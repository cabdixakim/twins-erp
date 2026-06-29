<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\InventoryPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InventorySettingsController extends Controller
{
    public function index()
    {
        $company = Company::findOrFail(auth()->user()->active_company_id);

        $openPeriod    = InventoryPeriod::where('company_id', $company->id)
            ->where('status', 'open')
            ->first();

        $closedPeriods = InventoryPeriod::where('company_id', $company->id)
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->get();

        $canChangeCosting = $company->canChangeCosting();

        return view('settings.inventory', compact('company', 'openPeriod', 'closedPeriods', 'canChangeCosting'));
    }

    public function updateCosting(Request $request)
    {
        $company = Company::findOrFail(auth()->user()->active_company_id);

        if (!$company->canChangeCosting()) {
            return back()->withErrors(['costing_method' => 'Costing method cannot be changed after inventory movements have been posted. Start a new inventory period to change it.']);
        }

        $request->validate([
            'costing_method' => ['required', 'in:weighted_average,specific_lot'],
        ]);

        $company->update(['costing_method' => $request->costing_method]);

        return back()->with('status', 'Costing method updated successfully.');
    }

    public function closePeriod(Request $request)
    {
        $company = Company::findOrFail(auth()->user()->active_company_id);

        $request->validate([
            'new_costing_method' => ['required', 'in:weighted_average,specific_lot'],
            'new_period_name'    => ['required', 'string', 'max:100'],
        ]);

        $openPeriod = InventoryPeriod::where('company_id', $company->id)
            ->where('status', 'open')
            ->first();

        if (!$openPeriod) {
            return back()->withErrors(['period' => 'No open period found.']);
        }

        $openPeriod->update([
            'status'    => 'closed',
            'ends_at'   => Carbon::now(),
            'closed_at' => Carbon::now(),
            'closed_by' => auth()->id(),
        ]);

        InventoryPeriod::create([
            'company_id'     => $company->id,
            'name'           => $request->new_period_name,
            'costing_method' => $request->new_costing_method,
            'starts_at'      => Carbon::now(),
            'status'         => 'open',
            'created_by'     => auth()->id(),
        ]);

        $company->update(['costing_method' => $request->new_costing_method]);

        return back()->with('status', 'Period closed. New period started with ' . ($request->new_costing_method === 'weighted_average' ? 'Weighted Average' : 'Specific Lot') . ' costing.');
    }
}
