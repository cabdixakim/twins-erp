<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::orderBy('name')->get();
        $currentSlug = $request->query('role', $roles->first()?->slug);

        $currentRole = $roles->firstWhere('slug', $currentSlug);

        $permissions = Permission::orderBy('module')->orderBy('name')->get();

        $assignedIds = $currentRole
            ? $currentRole->permissions()->pluck('permissions.id')->toArray()
            : [];

        return view('admin.roles.index', [
            'roles'       => $roles,
            'currentRole' => $currentRole,
            'permissions' => $permissions,
            'assignedIds' => $assignedIds,
        ]);
    }

  public function syncPermissions(Request $request, Role $role)
{
    $data = $request->validate([
        'permissions'   => 'array',
        'permissions.*' => 'integer|exists:permissions,id',
    ]);

    $role->permissions()->sync($data['permissions'] ?? []);

    return redirect()
        ->route('admin.roles.index', ['role' => $role->slug])
        ->with('status', 'Permissions updated for ' . $role->name . '.');
}

}