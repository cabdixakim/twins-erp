<?php

namespace Tests\Feature\Settings;

use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuppliersTest extends TestCase
{
    use RefreshDatabase;

    protected function createOwnerUser(): User
    {
        $role = Role::create([
            'name'        => 'Owner',
            'slug'        => 'owner',
            'description' => 'Full access',
            'is_system'   => true,
        ]);

        return User::create([
            'name'     => 'Owner User',
            'email'    => 'owner@example.com',
            'password' => bcrypt('password'),
            'status'   => 'active',
            'role_id'  => $role->id,
        ]);
    }

    public function test_owner_can_view_suppliers_index(): void
    {
        $user = $this->createOwnerUser();

        $this->actingAs($user);

        $response = $this->get(route('settings.suppliers.index'));

        $response->assertStatus(200);
        $response->assertSee('Suppliers');
    }

    public function test_owner_can_create_supplier(): void
    {
        $user = $this->createOwnerUser();

        $this->actingAs($user);

        $response = $this->post(route('settings.suppliers.store'), [
            'name'             => 'Dar Port',
            'type'             => 'port',
            'country'          => 'Tanzania',
            'city'             => 'Dar es Salaam',
            'default_currency' => 'USD',
            'is_active'        => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('suppliers', [
            'name'    => 'Dar Port',
            'type'    => 'port',
            'country' => 'Tanzania',
        ]);
    }

    public function test_owner_can_toggle_supplier_active(): void
    {
        $user = $this->createOwnerUser();
        $this->actingAs($user);

        $supplier = Supplier::create([
            'name'             => 'Test Supplier',
            'type'             => 'port',
            'country'          => 'TZ',
            'default_currency' => 'USD',
            'is_active'        => true,
        ]);

        $this->patch(route('settings.suppliers.toggle-active', $supplier))
             ->assertRedirect();

        $this->assertDatabaseHas('suppliers', [
            'id'        => $supplier->id,
            'is_active' => false,
        ]);
    }
}