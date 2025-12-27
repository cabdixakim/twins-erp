<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Active company id from authenticated user.
     */
    protected function activeCompanyId(): int
    {
        return (int) (auth()->user()?->active_company_id ?? 0);
    }

    /**
     * Abort if the given user is NOT a member of the active company.
     * (Multi-company safety for all admin actions.)
     */
    protected function abortIfNotInActiveCompany(User $user): void
    {
        $companyId = $this->activeCompanyId();

        if (!$companyId) {
            abort(404);
        }

        // user must belong to active company
        $belongs = $user->companies()
            ->whereKey($companyId)
            ->exists();

        if (!$belongs) {
            abort(404);
        }
    }

    /**
     * Prevent dangerous actions against the system owner.
     */
    protected function abortIfOwner(User $user): void
    {
        if ($user->role?->slug === 'owner') {
            abort(403, 'Owner account cannot be modified.');
        }
    }

    public function index()
    {
        $activeCompanyId = $this->activeCompanyId();

        $users = User::with('role')
            ->when($activeCompanyId, function ($q) use ($activeCompanyId) {
                $q->whereHas('companies', fn ($qq) => $qq->where('companies.id', $activeCompanyId));
            })
            ->orderBy('name')
            ->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $activeCompanyId = $this->activeCompanyId();
        if (!$activeCompanyId) {
            abort(404);
        }

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'role_id'  => 'required|exists:roles,id',
            'password' => 'nullable|string|min:6',
        ]);

        $plainPassword = $data['password'] ?: Str::password(12);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($plainPassword),
            'role_id'  => $data['role_id'],
            'status'   => 'active',
        ]);

        // Attach new user to active company
        if (method_exists($user, 'companies')) {
            $user->companies()->syncWithoutDetaching([$activeCompanyId]);

            // Default their active company
            if (!$user->active_company_id) {
                $user->active_company_id = $activeCompanyId;
                $user->save();
            }
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User created successfully.')
            ->with('generated_password', $plainPassword)
            ->with('generated_user_email', $user->email);
    }

    public function update(Request $request, User $user)
    {
        $this->abortIfNotInActiveCompany($user);

        // Owner cannot be modified (role/status/email changes etc)
        $this->abortIfOwner($user);

        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'status'  => 'required|in:active,inactive',
        ]);

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated.');
    }

    public function toggleStatus(User $user)
    {
        $this->abortIfNotInActiveCompany($user);

        // Prevent deactivating the owner
        if ($user->role?->slug === 'owner') {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'The owner account cannot be deactivated.');
        }

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User status updated.');
    }

    public function resetPassword(User $user)
    {
        $this->abortIfNotInActiveCompany($user);

        // Owner cannot be modified
        $this->abortIfOwner($user);

        $newPassword = Str::password(12);

        $user->password = Hash::make($newPassword);
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('generated_password', $newPassword)
            ->with('generated_user_email', $user->email);
    }

    public function destroy(User $user)
    {
        $activeCompanyId = $this->activeCompanyId();
        if (!$activeCompanyId) {
            abort(404);
        }

        $this->abortIfNotInActiveCompany($user);

        // Can't delete yourself
        if (auth()->id() === $user->id) {
            return redirect()
                ->route('admin.users.index')
                ->with('status', 'You cannot delete your own account.');
        }

        // Owner cannot be deleted
        if ($user->role?->slug === 'owner') {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'The owner account cannot be deleted.');
        }

        // Multi-company correct behaviour:
        // Remove from THIS company. Only hard-delete if they belong to no companies afterward.
        if (method_exists($user, 'companies')) {
            $user->companies()->detach([$activeCompanyId]);

            $stillHasCompanies = $user->companies()->exists();

            if ($stillHasCompanies) {
                // If their active company was this one, pick another (or null)
                if ((int) $user->active_company_id === $activeCompanyId) {
                    $newActive = $user->companies()->orderBy('companies.id')->value('companies.id');
                    $user->active_company_id = $newActive ?: null;
                    $user->save();
                }

                return redirect()
                    ->route('admin.users.index')
                    ->with('status', 'User removed from this company.');
            }
        }

        // No remaining company memberships → delete user record
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User deleted.');
    }
}