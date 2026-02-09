<?php

namespace Tests\Feature\Settings;

use App\Models\Depot;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class DepotsTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions so "owner" exists
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createOwnerUser(): User
    {
        $ownerRole = Role::where('slug', 'owner')->firstOrFail();

        return User::create([
            'name'      => 'Owner User',
            'email'     => 'owner@example.com',
            'password'  => bcrypt('password'),
            'role_id'   => $ownerRole->id,
            'is_active' => true,
        ]);
    }

    protected function createCompany(): \App\Models\Company
    {
        return \App\Models\Company::create([
            'name' => 'Test Company',
            'code' => 'TST',
            'slug' => 'test-company',
            'base_currency' => 'USD',
            'country' => 'US',
            'timezone' => 'UTC',
        ]);
    }

    protected function createOwnerUserWithCompany(): array
    {
        $company = $this->createCompany();
        $ownerRole = \App\Models\Role::where('slug', 'owner')->firstOrFail();
        $user = \App\Models\User::create([
            'name'      => 'Owner User',
            'email'     => 'owner@example.com',
            'password'  => bcrypt('password'),
            'role_id'   => $ownerRole->id,
            'is_active' => true,
        ]);
        $company->users()->attach($user->id);
        return compact('user', 'company');
    }

    /** @test */
    public function owner_can_view_depots_page(): void
    {
        extract($this->createOwnerUserWithCompany());

        $response = $this
            ->actingAs($user)
            ->get(route('settings.depots.index'));

        $response->assertStatus(200);
        $response->assertSee('Depots');
    }

    /** @test */
    public function owner_can_create_depot(): void
    {
        extract($this->createOwnerUserWithCompany());

        $payload = [
            'name'                   => 'Main Depot',
            'city'                   => 'Lubumbashi',
            'country'                => 'DRC',
            'storage_fee_per_1000_l' => 24.00,
            'default_shrinkage_pct'  => 0.300,
            'is_active'              => 1,
            'notes'                  => 'Test depot',
            'company_id'             => $company->id,
        ];

        $response = $this
            ->actingAs($user)
            ->post(route('settings.depots.store'), $payload);

        $response->assertRedirect(route('settings.depots.index'));
        $this->assertDatabaseHas('depots', [
            'name'  => 'Main Depot',
            'city'  => 'Lubumbashi',
            'is_active' => 1,
        ]);
    }

    /** @test */
    public function owner_can_update_depot(): void
    {
        extract($this->createOwnerUserWithCompany());

        $depot = Depot::create([
            'name'                   => 'Old Name',
            'city'                   => 'Old City',
            'country'                => 'DRC',
            'storage_fee_per_1000_l' => 10.00,
            'default_shrinkage_pct'  => 0.200,
            'is_active'              => true,
            'notes'                  => null,
            'company_id'             => $company->id,
        ]);

        $payload = [
            'name'                   => 'New Name',
            'city'                   => 'New City',
            'country'                => 'DRC',
            'storage_fee_per_1000_l' => 30.50,
            'default_shrinkage_pct'  => 0.350,
            'is_active'              => 1,
            'notes'                  => 'Updated note',
        ];

        $response = $this
            ->actingAs($user)
            ->patch(route('settings.depots.update', $depot), $payload);

        $response->assertRedirect(route('settings.depots.index', ['depot' => $depot->id]));

        $this->assertDatabaseHas('depots', [
            'id'                     => $depot->id,
            'name'                   => 'New Name',
            'city'                   => 'New City',
            'storage_fee_per_1000_l' => 30.50,
            'default_shrinkage_pct'  => 0.350,
            'is_active'              => 1,
        ]);
    }

    /** @test */
    public function owner_can_toggle_depot_active_state(): void
    {
        extract($this->createOwnerUserWithCompany());

        $depot = Depot::create([
            'name'                   => 'Toggle Depot',
            'city'                   => 'Lubumbashi',
            'country'                => 'DRC',
            'storage_fee_per_1000_l' => 20.00,
            'default_shrinkage_pct'  => 0.300,
            'is_active'              => true,
            'notes'                  => null,
            'company_id'             => $company->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('settings.depots.toggle-active', $depot));

        $response->assertRedirect(route('settings.depots.index', ['depot' => $depot->id]));

        $this->assertDatabaseHas('depots', [
            'id'        => $depot->id,
            'is_active' => false,
        ]);
    }
}