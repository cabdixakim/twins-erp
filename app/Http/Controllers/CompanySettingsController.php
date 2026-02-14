<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;

class CompanyController extends Controller
{
    // ...existing code...

    public function update(Request $request)
    {
        $u = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:32',
            // ...other fields...
        ]);
        // Check for duplicate company code or name (global)
        $exists = \App\Models\Company::query()
            ->where(function($q) use ($data, $cid) {
                $q->where('code', $data['code'])
                  ->orWhere('name', $data['name']);
            })
            ->where('id', '!=', $cid)
            ->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'A company with this code or name already exists.'])->withInput();
        }
        // ...existing code for updating company...
    }

    // ...existing code...
}