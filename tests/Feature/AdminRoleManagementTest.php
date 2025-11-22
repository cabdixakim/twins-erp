<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

class AdminRoleManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles + permissions for these tests
        $this->seed(RolePermissionSeeder::class);
    }

    // --------------------------------------------------
    // Helpers
    // --------------------------------------------------

    protected function createOwnerUser(): User
    {
        $ownerRole = Role::where('slug', 'owner')->firstOrFail();

        $user = new User();
        $user->name     = 'Owner Admin';
        $user->email    = 'owner-admin@example.test';
        $user->password = bcrypt('password'); // plain password irrelevant for tests
        $user->role_id  = $ownerRole->id;
        $user->status   = 'active';
        $user->save();

        return $user;
    }

    protected function createManagerRole(): Role
    {
        // you already have a manager role from the seeder, but
        // just in case, make sure we get one
        return Role::where('slug', 'manager')->firstOr(function () {
            $role = new Role();
            $role->name        = 'Manager';
            $role->slug        = 'manager';
            $role->description = 'Manager role for tests';
            $role->is_system   = false;
            $role->save();

            return $role;
        });
    }

    // --------------------------------------------------
    // Tests
    // --------------------------------------------------

    /** @test */
    public function admin_can_create_a_new_role(): void
    {
        $admin = $this->createOwnerUser();
        $this->actingAs($admin);

        $permIds = Permission::whereIn('slug', [
            'depots.view',
            'inventory.view',
        ])->pluck('id')->all();

        $response = $this->post(route('admin.roles.store'), [
            'name'        => 'Supervisor',
            'slug'        => 'supervisor',
            'description' => 'Supervisor role created by test',
            'permissions' => $permIds,
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('roles', [
            'slug' => 'supervisor',
        ]);

        $role = Role::where('slug', 'supervisor')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            $permIds,
           $role->permissions()->pluck('permissions.id')->all()
        );
    }

    /** @test */
    public function admin_can_update_a_roles_permissions(): void
    {
        $admin = $this->createOwnerUser();
        $this->actingAs($admin);

        $role = $this->createManagerRole();

        $newPermIds = Permission::whereIn('slug', [
            'sales.view',
            'sales.create',
        ])->pluck('id')->all();

        $response = $this->put(route('admin.roles.update', $role), [
            'name'        => $role->name,
            'slug'        => $role->slug,
            'description' => $role->description,
            'permissions' => $newPermIds,
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $this->assertEqualsCanonicalizing(
            $newPermIds,
            $role->fresh()->permissions()->pluck('permissions.id')->all()
        );
    }

    /** @test */
    public function permissions_can_be_cleared_by_sending_empty_array(): void
    {
        $admin = $this->createOwnerUser();
        $this->actingAs($admin);

        $role = $this->createManagerRole();

        // Give it some permissions first
        $permIds = Permission::pluck('id')->take(3)->all();
        $role->permissions()->sync($permIds);
        $this->assertNotEmpty($role->fresh()->permissions);

        // Now clear by sending empty array
        $response = $this->put(route('admin.roles.update', $role), [
            'name'        => $role->name,
            'slug'        => $role->slug,
            'description' => $role->description,
            'permissions' => [], // important bit
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $this->assertCount(0, $role->fresh()->permissions);
    }
}