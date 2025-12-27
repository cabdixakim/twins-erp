<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanySwitcherController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('companies.switcher', [
            'companies' => $user->companies()->orderBy('name')->get(),
            'activeId'  => $user->active_company_id,
        ]);
    }

    public function switch(Request $request, Company $company)
    {
        $user = $request->user();

        abort_unless($user->companies()->whereKey($company->id)->exists(), 403);

        $user->active_company_id = $company->id;
        $user->save();

        return redirect()->route('dashboard');
    }
}