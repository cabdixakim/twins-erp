<?php

namespace Tests\Feature\Settings;

use App\Models\Role;
use App\Models\Transporter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportersTest extends TestCase
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

    public function test_owner_can_view_transporters_index(): void
    {
        $user = $this->createOwnerUser();
        $this->actingAs($user);

        $response = $this->get(route('settings.transporters.index'));

        $response->assertStatus(200);
        $response->assertSee('Transporters');
    }

    public function test_owner_can_create_transporter(): void
    {
        $user = $this->createOwnerUser();
        $this->actingAs($user);

        $response = $this->post(route('settings.transporters.store'), [
            'name'                   => 'ABC Logistics',
            'type'                   => 'intl',
            'country'                => 'Tanzania',
            'city'                   => 'Dar es Salaam',
            'default_currency'       => 'USD',
            'default_rate_per_1000_l'=> 45.1234,
            'payment_terms'          => '30 days',
            'is_active'              => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transporters', [
            'name'    => 'ABC Logistics',
            'type'    => 'intl',
            'country' => 'Tanzania',
        ]);
    }

    public function test_owner_can_toggle_transporter_active(): void
    {
        $user = $this->createOwnerUser();
        $this->actingAs($user);

        $t = Transporter::create([
            'name'                   => 'Test Transporter',
            'type'                   => 'local',
            'default_currency'       => 'USD',
            'default_rate_per_1000_l'=> 10,
            'is_active'              => true,
        ]);

        $this->patch(route('settings.transporters.toggle-active', $t))
             ->assertRedirect();

        $this->assertDatabaseHas('transporters', [
            'id'        => $t->id,
            'is_active' => false,
        ]);
    }
}