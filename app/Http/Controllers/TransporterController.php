<?php

namespace App\Http\Controllers;

use App\Models\Transporter;
use Illuminate\Http\Request;

class TransporterController extends Controller
{
    // ...existing code...

    public function store(Request $request)
    {
        $u = auth()->user();
        $cid = (int) ($u?->active_company_id ?? 0);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'nullable|string|max:32',
            // ...other fields...
        ]);
        // Check for duplicate transporter (company_id + name)
        $exists = \App\Models\Transporter::query()
            ->where('company_id', $cid)
            ->where('name', $data['name'])
            ->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'A transporter with this name already exists for your company.'])->withInput();
        }
        // ...existing code for creating transporter...
    }

    // ...existing code...
}