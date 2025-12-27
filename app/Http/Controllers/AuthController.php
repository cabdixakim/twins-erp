<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;

class AuthController extends Controller
{
    public function showLogin()
    {
        // ✅ First-run: if no company exists, do NOT show login.
        if (Company::query()->count() === 0) {
            return redirect()->route('company.create');
        }

        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        // ✅ First-run: if no company exists, block login and go to setup.
        if (Company::query()->count() === 0) {
            return redirect()->route('company.create');
        }

        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Invalid credentials.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        // ✅ multi-company login routing (keep your logic)
        $user = $request->user();

        $companyCount = method_exists($user, 'companies') ? $user->companies()->count() : 0;

        if ($companyCount === 0) {
            return redirect()->route('company.create');
        }

        if (!$user->active_company_id || !$user->companies()->whereKey($user->active_company_id)->exists()) {
            $firstCompanyId = $user->companies()->orderBy('companies.id')->value('companies.id');
            $user->active_company_id = $firstCompanyId;
            $user->save();
        }

        if ($companyCount > 1) {
            return redirect()->route('companies.switcher');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}