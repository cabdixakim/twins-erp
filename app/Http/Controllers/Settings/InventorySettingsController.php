<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\InventoryPeriod;
use Illuminate\Http\Request;

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
}
