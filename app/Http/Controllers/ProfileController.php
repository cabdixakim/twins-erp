<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        $isOwner = $user->role?->slug === 'owner';

        return view('profile.index', compact('user', 'isOwner'));
    }

    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->password = Hash::make($data['password']);
        $user->save();

        return back()->with('status', 'Password changed successfully.');
    }

    public function generateRecoveryToken(Request $request)
    {
        $user = auth()->user();

        if ($user->role?->slug !== 'owner') {
            abort(403, 'Only the owner can generate a recovery token.');
        }

        $plain = Str::upper(implode('-', str_split(Str::random(16), 4)));
        $user->recovery_token = Hash::make($plain);
        $user->save();

        return back()
            ->with('status', 'Recovery token generated.')
            ->with('recovery_plain', $plain);
    }

    public function clearRecoveryToken()
    {
        $user = auth()->user();

        if ($user->role?->slug !== 'owner') {
            abort(403);
        }

        $user->recovery_token = null;
        $user->save();

        return back()->with('status', 'Recovery token cleared.');
    }
}
