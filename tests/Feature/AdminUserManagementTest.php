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

    protected function createOwnerUserWithCompany(): array
    {
        $company = \App\Models\Company::create([
            'name' => 'Test Company',
            'code' => 'TST',
            'slug' => 'test-company',
            'base_currency' => 'USD',
            'country' => 'US',
            'timezone' => 'UTC',
        ]);
        $role = $this->createRole('Owner', 'owner');
        $user = $this->createUser($role);
        $company->users()->attach($user->id);
        return compact('user', 'company');
    }

    // ---------------------------------------------------------------------
    // Tests
    // ---------------------------------------------------------------------

    /** @test */
    public function owner_can_view_users_page(): void
    {
        extract($this->createOwnerUserWithCompany());
        $this->actingAs($user);

        $response = $this->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Team members');
    }

    /** @test */
    public function non_owner_cannot_access_admin_users(): void
    {
        $company = \App\Models\Company::create([
            'name' => 'Test Company',
            'code' => 'TST',
            'slug' => 'test-company',
            'base_currency' => 'USD',
            'country' => 'US',
            'timezone' => 'UTC',
        ]);
        $ownerRole = $this->createRole('Owner', 'owner');
        $staffRole = $this->createRole('Accountant', 'accountant');

        // normal user with non-owner role
        $user = $this->createUser($staffRole, [
            'name'  => 'Staff',
            'email' => 'staff@example.test',
        ]);
        $company->users()->attach($user->id);

        $this->actingAs($user);

        $this->get(route('admin.users.index'))
            ->assertStatus(403);
    }

    /** @test */
    public function owner_can_create_user_with_manual_password(): void
    {
        extract($this->createOwnerUserWithCompany());
        $this->actingAs($user);
        $staffRole = $this->createRole('Accountant', 'accountant');

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
        extract($this->createOwnerUserWithCompany());
        $this->actingAs($user);
        $staffRole = $this->createRole('Accountant', 'accountant');

        $staff = $this->createUser($staffRole, [
            'name'  => 'Staff',
            'email' => 'staff@example.test',
        ]);
        $company->users()->attach($staff->id);

        // Deactivate
        $this->post(route('admin.users.toggle-status', $staff));
        $this->assertEquals('inactive', $staff->fresh()->status);

        // Reactivate
        $this->post(route('admin.users.toggle-status', $staff));
        $this->assertEquals('active', $staff->fresh()->status);
    }

    /** @test */
    public function owner_cannot_be_deactivated_or_deleted(): void
    {
        extract($this->createOwnerUserWithCompany());
        $this->actingAs($user);

        // Try to deactivate owner
        $this->post(route('admin.users.toggle-status', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertEquals('active', $user->fresh()->status);

        // Try to delete owner
        $this->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }

    /** @test */
    public function owner_can_reset_user_password_and_plain_value_is_in_session(): void
    {
        extract($this->createOwnerUserWithCompany());
        $this->actingAs($user);
        $staffRole = $this->createRole('Accountant', 'accountant');

        $staff = $this->createUser($staffRole, [
            'name'  => 'Staff',
            'email' => 'staff@example.test',
        ]);
        $company->users()->attach($staff->id);

        $oldHash = $staff->password;

        $response = $this->post(route('admin.users.reset-password', $staff));

        $response
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('generated_password')
            ->assertSessionHas('generated_user_email', $staff->email);

        $this->assertNotEquals($oldHash, $staff->fresh()->password);
    }
}