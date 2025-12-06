<?php
// app/Http/Controllers/Settings/CompanySettingsController.php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanySettingsController extends Controller
{
    public function edit()
    {
        // Single-company install – just grab the first row
        $company = Company::firstOrFail();

        return view('settings.company', [
            'company' => $company,
        ]);
    }

    public function update(Request $request)
    {
        $company = Company::firstOrFail();

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'base_currency' => ['nullable', 'string', 'max:10'],
            'country'       => ['nullable', 'string', 'max:255'],
            'timezone'      => ['nullable', 'string', 'max:255'],
            'logo'          => ['nullable', 'image', 'max:2048'], // 2MB
        ]);

        // handle logo upload
        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $path = $request->file('logo')->store('company-logos', 'public');
            $data['logo_path'] = $path;
        }

        $company->fill($data)->save();

        return redirect()
            ->route('settings.company.edit')
            ->with('status', 'Company profile updated.');
    }
}