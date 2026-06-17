<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DutyVendor;

class DutyVendorController extends Controller
{
    public function index()
    {
        $cid = (int) auth()->user()->active_company_id;

        $vendors = DutyVendor::where('company_id', $cid)
            ->orderBy('name')
            ->get();

        return view('settings.duty-vendors', compact('vendors'));
    }

    public function store(Request $request)
    {
        $cid = (int) auth()->user()->active_company_id;

        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'code'             => 'nullable|string|max:20',
            'country'          => 'nullable|string|max:80',
            'city'             => 'nullable|string|max:80',
            'contact_person'   => 'nullable|string|max:150',
            'phone'            => 'nullable|string|max:50',
            'default_currency' => 'nullable|string|max:8',
            'notes'            => 'nullable|string|max:1000',
        ]);

        DutyVendor::create(array_merge($data, [
            'company_id'       => $cid,
            'default_currency' => $data['default_currency'] ?: 'USD',
            'is_active'        => true,
            'created_by'       => auth()->id(),
        ]));

        return redirect()->route('settings.duty-vendors.index')
            ->with('status', 'Customs authority "' . $data['name'] . '" created.');
    }

    public function update(Request $request, DutyVendor $dutyVendor)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $dutyVendor->company_id !== $cid, 403);

        $data = $request->validate([
            'name'             => 'required|string|max:150',
            'code'             => 'nullable|string|max:20',
            'country'          => 'nullable|string|max:80',
            'city'             => 'nullable|string|max:80',
            'contact_person'   => 'nullable|string|max:150',
            'phone'            => 'nullable|string|max:50',
            'default_currency' => 'nullable|string|max:8',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $dutyVendor->update(array_merge($data, [
            'default_currency' => $data['default_currency'] ?: 'USD',
        ]));

        return redirect()->route('settings.duty-vendors.index')
            ->with('status', 'Customs authority updated.');
    }

    public function toggleActive(DutyVendor $dutyVendor)
    {
        $cid = (int) auth()->user()->active_company_id;
        abort_if((int) $dutyVendor->company_id !== $cid, 403);

        $dutyVendor->update(['is_active' => !$dutyVendor->is_active]);

        return back()->with('status', $dutyVendor->is_active ? 'Authority activated.' : 'Authority deactivated.');
    }
}
