<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', [
            'users' => User::with('role')->orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }


// ...

public function store(Request $request)
{
    $data = $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|max:255|unique:users,email',
        'role_id'  => 'required|exists:roles,id',
        'password' => 'nullable|string|min:6', // admin MAY enter it
    ]);

    // If admin typed a password, use that; otherwise generate one.
    $plainPassword = $data['password'] ?: Str::random(10);

    $user = User::create([
        'name'     => $data['name'],
        'email'    => $data['email'],
        'password' => Hash::make($plainPassword),
        'role_id'  => $data['role_id'],
        'status'   => 'active',
    ]);

    return redirect()->route('admin.users.index')
        ->with('status', 'User created successfully.')
        ->with('generated_password', $plainPassword)       // show whatever was used
        ->with('generated_user_email', $user->email);
}
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'status'  => 'required|in:active,inactive',
        ]);

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('status', 'User updated.');
    }

  public function toggleStatus(User $user)
{
    // Prevent deactivating the owner
    if ($user->role?->slug === 'owner') {
        return redirect()
            ->route('admin.users.index')
            ->with('error', 'The owner account cannot be deactivated.');
    }

    $user->status = $user->status === 'active' ? 'inactive' : 'active';
    $user->save();

    return redirect()->route('admin.users.index')
        ->with('success', 'User status updated.');
}

    // Now: generate random password instead of manual input
 public function resetPassword(User $user)
{
    // Prevent resetting owner password? (optional)
    // If you want: if ($user->role?->slug === 'owner') abort(403);

    $newPassword = Str::password(12);

    $user->password = bcrypt($newPassword);
    $user->save();

    return redirect()
        ->route('admin.users.index')
        ->with('generated_password', $newPassword)
        ->with('generated_user_email', $user->email);
}
    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('status', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('status', 'User deleted.');
    }
}