<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    protected function createRole(string $name, string $slug): Role
    {
        $id = DB::table('roles')->insertGetId([
            'name'       => $name,
            'slug'       => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Role::findOrFail($id);
    }

    protected function createUser(Role $role, array $overrides = []): User
    {
        $defaults = [
            'name'       => 'Owner User',
            'email'      => 'owner@example.test',
            'password'   => Hash::make('secret-123'),
            'role_id'    => $role->id,
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = DB::table('users')->insertGetId(array_merge($defaults, $overrides));

        return User::findOrFail($id);
    }

    protected function actingAsOwner(): array
    {
        $ownerRole = $this->createRole('Owner', 'owner');
        $owner     = $this->createUser($ownerRole);

        $this->actingAs($owner);

        return [$owner, $ownerRole];
    }

    // ---------------------------------------------------------------------
    // Tests
    // ---------------------------------------------------------------------

    /** @test */
    public function owner_can_view_users_page(): void
    {
        [$owner] = $this->actingAsOwner();

        $response = $this->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Team members');
    }

    /** @test */
    public function non_owner_cannot_access_admin_users(): void
    {
        $ownerRole = $this->createRole('Owner', 'owner');
        $staffRole = $this->createRole('Accountant', 'accountant');

        // normal user with non-owner role
        $user = $this->createUser($staffRole, [
            'name'  => 'Staff',
            'email' => 'staff@example.test',
        ]);

        $this->actingAs($user);

        $this->get(route('admin.users.index'))
            ->assertStatus(403);
    }

    /** @test */
    public function owner_can_create_user_with_manual_password(): void
    {
        [$owner, $ownerRole] = $this->actingAsOwner();
        $staffRole           = $this->createRole('Accountant', 'accountant');

        $plainPassword = 'MegaStrong123!';

        $response = $this->post(route('admin.users.store'), [
            'name'    => 'New Staff',
            'email'   => 'newstaff@example.test',
            'role_id' => $staffRole->id,
            'password'=> $plainPassword,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email'   => 'newstaff@example.test',
            'role_id' => $staffRole->id,
        ]);

        $created = User::where('email', 'newstaff@example.test')->firstOrFail();
        $this->assertTrue(Hash::check($plainPassword, $created->password));
    }

    /** @test */
    public function owner_can_deactivate_and_reactivate_a_normal_user(): void
    {
        [$owner, $ownerRole] = $this->actingAsOwner();
        $staffRole           = $this->createRole('Accountant', 'accountant');

        $user = $this->createUser($staffRole, [
            'name'  => 'Staff',
            'email' => 'staff@example.test',
        ]);

        // Deactivate
        $this->post(route('admin.users.toggle-status', $user));
        $this->assertEquals('inactive', $user->fresh()->status);

        // Reactivate
        $this->post(route('admin.users.toggle-status', $user));
        $this->assertEquals('active', $user->fresh()->status);
    }

    /** @test */
    public function owner_cannot_be_deactivated_or_deleted(): void
    {
        [$owner, $ownerRole] = $this->actingAsOwner();

        // Try to deactivate owner
        $this->post(route('admin.users.toggle-status', $owner))
            ->assertRedirect(route('admin.users.index'));

        $this->assertEquals('active', $owner->fresh()->status);

        // Try to delete owner
        $this->delete(route('admin.users.destroy', $owner))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
        ]);
    }

    /** @test */
    public function owner_can_reset_user_password_and_plain_value_is_in_session(): void
    {
        [$owner, $ownerRole] = $this->actingAsOwner();
        $staffRole           = $this->createRole('Accountant', 'accountant');

        $user = $this->createUser($staffRole, [
            'name'  => 'Staff',
            'email' => 'staff@example.test',
        ]);

        $oldHash = $user->password;

        $response = $this->post(route('admin.users.reset-password', $user));

        $response
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('generated_password')
            ->assertSessionHas('generated_user_email', $user->email);

        $this->assertNotEquals($oldHash, $user->fresh()->password);
    }
}