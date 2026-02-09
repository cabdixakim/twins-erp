<?php
// app/Http/Controllers/Settings/CompanySettingsController.php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'code'          => ['required', 'string', 'alpha_num', 'min:2', 'max:10', 'unique:companies,code,' . $company->id],
            'base_currency' => ['nullable', 'string', 'max:10'],
            'country'       => ['nullable', 'string', 'max:255'],
            'timezone'      => ['nullable', 'string', 'max:255'],

            // normal upload (fallback if cropper isn't used)
            'logo'          => ['nullable', 'image', 'max:2048'], // 2MB

            // cropper output (data URL)
            'logo_cropped'  => ['nullable', 'string'],

            // remove flag
            'remove_logo'   => ['nullable', 'boolean'],
        ]);

        // 1) Remove logo if requested
        if ($request->boolean('remove_logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = null;

            // If user also uploaded/cropped, removal wins (explicit intent)
            unset($data['logo_cropped']);
        }

        // 2) Handle cropped logo (preferred if provided)
        if (!$request->boolean('remove_logo') && $request->filled('logo_cropped')) {
            $raw = (string) $request->input('logo_cropped');

            // Expect: data:image/png;base64,....
            if (preg_match('/^data:image\/(\w+);base64,/', $raw, $m)) {
                $ext = strtolower($m[1]);
                if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                    return back()
                        ->withErrors(['logo' => 'Unsupported image type.'])
                        ->withInput();
                }

                $base64 = substr($raw, strpos($raw, ',') + 1);
                $base64 = str_replace(' ', '+', $base64);
                $bin = base64_decode($base64);

                if ($bin === false) {
                    return back()
                        ->withErrors(['logo' => 'Could not decode cropped image.'])
                        ->withInput();
                }

                // delete old logo
                if ($company->logo_path) {
                    Storage::disk('public')->delete($company->logo_path);
                }

                $filename = 'company-logos/' . now()->format('Ymd_His') . '_' . Str::random(10) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
                Storage::disk('public')->put($filename, $bin);

                $data['logo_path'] = $filename;
            }
        }

        // 3) Handle normal upload (only if no cropped data and not removed)
        if (
            !$request->boolean('remove_logo')
            && $request->hasFile('logo')
            && !$request->filled('logo_cropped')
        ) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $path = $request->file('logo')->store('company-logos', 'public');
            $data['logo_path'] = $path;
        }

        // prevent mass-assign trying to fill non-columns
        unset($data['logo'], $data['logo_cropped'], $data['remove_logo']);

        $company->fill($data)->save();

        return redirect()
            ->route('settings.company.edit')
            ->with('status', 'Company profile updated.');
    }
}