<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Depot;

class CompanyController extends Controller
{
    public function create()
    {
        // Keep your existing behaviour:
        // if a company already exists in DB, block wizard and go to login.
        if (Company::count() > 0) {
            return redirect()->route('login');
        }

        return view('onboarding.company_create');
    }

    public function store(Request $request)
    {
        // 1) Validate input
        $data = $request->validate([
            'company_name'   => 'required|string|max:255',
            'code'           => 'required|string|alpha_num|unique:companies,code|min:2|max:10',
            'base_currency'  => 'required|string|max:10',
            'owner_name'     => 'required|string|max:255',
            'owner_email'    => 'required|email|max:255|unique:users,email',
            'owner_password' => 'required|string|min:6',
        ]);

        // 2) Create owner user
        $ownerRoleId = Role::where('slug', 'owner')->value('id');

        $user = User::create([
            'name'     => $data['owner_name'],
            'email'    => $data['owner_email'],
            'password' => Hash::make($data['owner_password']),
            'role_id'  => $ownerRoleId,
            'status'   => 'active',
        ]);

        // 3) Log the owner in
        Auth::login($user);
        $request->session()->regenerate();

        // 4) Create company record
        $company = Company::create([
            'name'          => $data['company_name'],
            'code'          => $data['code'],
            'slug'          => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['company_name'])) . '-' . uniqid(),
            'base_currency' => $data['base_currency'],
        ]);
        
        // ✅ ADDITION: create CROSS DOCK depot
        $this->ensureCrossDockDepot($company->id, $user->id ?? auth()->id());

        // ✅ ADDITION: attach membership + set active_company_id (multi-company)
        if (method_exists($user, 'companies')) {
            $user->companies()->syncWithoutDetaching([$company->id]);
        }
        $user->active_company_id = $company->id;
        $user->save();

        // 5) Keep your existing session key for backward compatibility (for now)
        session(['company_id' => $company->id]);

        // 6) Go to dashboard
        return redirect()->route('dashboard');
    }

    private function ensureCrossDockDepot(int $companyId, ?int $userId = null): void
    {
        Depot::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'name'       => 'CROSS DOCK',
            ],
            [
                'is_active'  => true,          // adjust if your column is `active`
                'is_system'  => true,          // only if you added the migration
                'created_by' => $userId,       // only if depots has created_by
            ]
        );
    }

}