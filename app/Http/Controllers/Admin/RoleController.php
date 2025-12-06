<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * List roles and permissions.
     */
    public function index(Request $request): View
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        // Group permissions by module for the UI
        $permissionsByModule = Permission::orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');

        return view('admin.roles.index', [
            'roles'              => $roles,
            'permissionsByModule'=> $permissionsByModule,
        ]);
    }

    /**
     * Create a new role.
     *
     * Used by tests via route('admin.roles.store').
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'slug'          => ['nullable', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'is_system'     => ['sometimes', 'boolean'],
            'permissions'   => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        // Generate slug if not given
        $slug = $data['slug'] ?? Str::slug($data['name']);

        // Ensure slug is unique
        $base = $slug;
        $i = 1;
        while (Role::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $role = Role::create([
            'name'        => $data['name'],
            'slug'        => $slug,
            'description' => $data['description'] ?? null,
            'is_system'   => $data['is_system'] ?? false,
        ]);

        // Attach permissions if provided
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role created.');
    }

    /**
     * Update a role (name / description / permissions).
     *
     * Used by tests via route('admin.roles.update').
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:255'],
            'slug'          => ['sometimes', 'nullable', 'string', 'max:255'],
            'description'   => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_system'     => ['sometimes', 'boolean'],
            'permissions'   => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        if (array_key_exists('name', $data)) {
            $role->name = $data['name'];
        }

        if (array_key_exists('slug', $data)) {
            $slug = $data['slug'] ?? Str::slug($role->name);
            $base = $slug;
            $i = 1;

            while (
                Role::where('slug', $slug)
                    ->where('id', '!=', $role->id)
                    ->exists()
            ) {
                $slug = $base . '-' . $i++;
            }

            $role->slug = $slug;
        }

        if (array_key_exists('description', $data)) {
            $role->description = $data['description'];
        }

        if (array_key_exists('is_system', $data)) {
            $role->is_system = $data['is_system'];
        }

        $role->save();

        // If permissions were sent, sync them (empty array clears them)
        if (array_key_exists('permissions', $data)) {
            $role->permissions()->sync($data['permissions'] ?? []);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role updated.');
    }

    /**
     * Delete a role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // Safety: never delete the core owner role
        if ($role->slug === 'owner') {
            return redirect()
                ->route('admin.roles.index')
                ->with('status', 'Owner role cannot be deleted.');
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Role deleted.');
    }

    /**
     * Sync permissions for a role from the roles screen.
     *
     * Used by the existing Blade UI and its form that posts to
     * route('admin.roles.permissions.sync', $role).
     */
    public function syncPermissions(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions'   => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Permissions updated.');
    }
}