<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AccountRecoveryController extends Controller
{
    public function show()
    {
        return view('auth.account-recovery');
    }

    public function recover(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::where('email', $data['email'])->first();

        // Only allow recovery of owner accounts
        if (!$user || $user->role?->slug !== 'owner') {
            return back()->withErrors(['email' => 'No owner account found with that email.'])->withInput(['email' => $data['email']]);
        }

        if (!$user->recovery_token || !Hash::check($data['token'], $user->recovery_token)) {
            return back()->withErrors(['token' => 'Recovery code is invalid or has already been used.'])->withInput(['email' => $data['email']]);
        }

        // Valid — reset password and clear the one-time token
        $user->password       = Hash::make($data['password']);
        $user->recovery_token = null;
        $user->status         = 'active';
        $user->save();

        // Log them in immediately
        Auth::login($user);

        return redirect('/dashboard')->with('status', 'Account recovered. Welcome back.');
    }
}
