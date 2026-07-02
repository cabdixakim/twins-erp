<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\AuditLog;

class MaintenanceRecoveryController extends Controller
{
    /**
     * Show the maintainer recovery form.
     * Requires a valid ?key= matching MAINTENANCE_RECOVERY_KEY, or the form
     * itself will prompt for the key on submit. The page 404s entirely if
     * the env key isn't configured, so this surface doesn't exist unless
     * a maintainer has deliberately turned it on.
     */
    public function show(Request $request)
    {
        if (!config('app.maintenance_recovery_key')) {
            abort(404);
        }

        return view('auth.maintenance-recovery');
    }

    public function recover(Request $request)
    {
        $configuredKey = config('app.maintenance_recovery_key');

        if (!$configuredKey) {
            abort(404);
        }

        $request->validate([
            'key'                   => ['required', 'string'],
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!hash_equals($configuredKey, (string) $request->input('key'))) {
            return back()->withErrors(['key' => 'Invalid recovery key.'])->withInput($request->except(['key', 'password', 'password_confirmation']));
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return back()->withErrors(['email' => 'No user found with that email.'])->withInput($request->except(['key', 'password', 'password_confirmation']));
        }

        if ($user->role?->slug !== 'owner') {
            return back()->withErrors(['email' => 'This tool can only recover owner accounts.'])->withInput($request->except(['key', 'password', 'password_confirmation']));
        }

        $user->password       = Hash::make($request->input('password'));
        $user->status         = 'active';
        $user->recovery_token = null;
        $user->save();

        AuditLog::record(
            event: 'updated',
            description: "Owner account [{$user->email}] recovered via maintainer recovery page (password reset + reactivated).",
            model: $user,
            modelLabel: "User {$user->email}",
            companyId: $user->active_company_id,
            severity: 'critical',
            module: 'Admin',
        );

        return redirect()->route('login')->with('status', 'Password reset successfully. You can now log in.');
    }
}
