<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\User;
use App\Models\Role;


class CompanyController extends Controller
{
    public function create()
    {
        // If a user already exists, just send to login
        if (User::count() > 0) {
            return redirect()->route('login');
        }

        return view('onboarding.company_create');
    }

    public function store(Request $request)
    {
        // 1) Validate input
        $data = $request->validate([
            'company_name'   => 'required|string|max:255',
            'base_currency'  => 'required|string|max:10',
            'owner_name'     => 'required|string|max:255',
            'owner_email'    => 'required|email|max:255|unique:users,email',
            'owner_password' => 'required|string|min:6',
        ]);

        // 2) Create owner user

        // ...

        // find owner role
        $ownerRoleId = Role::where('slug', 'owner')->value('id');

        // 2) Create owner user
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
            'slug'          => strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['company_name'])) . '-' . uniqid(),
            'base_currency' => $data['base_currency'],
        ]);

        // 5) Save active company in session for later use
        session(['company_id' => $company->id]);

        // 6) Go to dashboard
        return redirect()->route('dashboard');
    }
}