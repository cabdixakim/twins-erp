<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Make sure roles/permissions exist for the tests
        $this->seed(RolePermissionSeeder::class);
    }

    protected function createOwnerUserWithCompany(): array
    {
        $company = Company::create([
            'name' => 'Test Company',
            'code' => 'TST',
            'slug' => 'test-company',
            'base_currency' => 'USD',
            'country' => 'US',
            'timezone' => 'UTC',
        ]);
        $ownerRole = Role::where('slug', 'owner')->firstOrFail();
        $user = User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'role_id' => $ownerRole->id,
            'status' => 'active',
        ]);
        $company->users()->attach($user->id);
        return compact('user', 'company');
    }

    /** @test */
    public function guest_is_redirected_to_login_when_visiting_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    /** @test */
    public function login_page_is_accessible_for_guests(): void
    {
        // Ensure a company exists so the login page is accessible
        \App\Models\Company::create([
            'name' => 'Test Company',
            'code' => 'TST',
            'slug' => 'test-company',
            'base_currency' => 'USD',
            'country' => 'US',
            'timezone' => 'UTC',
        ]);
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Sign in to Twins');
    }

    /** @test */
    public function user_with_valid_credentials_can_log_in(): void
    {
        extract($this->createOwnerUserWithCompany());

        // Hit the login endpoint
        $response = $this->post('/login', [
            'email'    => 'owner@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function login_with_invalid_credentials_fails(): void
    {
        extract($this->createOwnerUserWithCompany());

        $response = $this->from('/login')->post('/login', [
            'email'    => 'owner@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /** @test */
    public function onboarding_creates_owner_and_company_and_logs_in(): void
    {
        // Make sure DB is empty (RefreshDatabase already does this)

        $payload = [
            'company_name'   => 'Twins Logistics',
            'code'          => 'TWINS',
            'base_currency'  => 'USD',
            'owner_name'     => 'Zak Owner',
            'owner_email'    => 'zak.owner@example.test',
            'owner_password' => 'supersecret',
        ];

        $response = $this->post('/company', $payload);

        // 1) Redirected to dashboard
        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard'));

        // 2) Company record created
        $this->assertDatabaseHas('companies', [
            'name' => 'Twins Logistics',
        ]);

        // 3) Owner user created, active, with role set
        $this->assertDatabaseHas('users', [
            'email'  => 'zak.owner@example.test',
            'status' => 'active',
        ]);

        $user = User::where('email', 'zak.owner@example.test')->firstOrFail();
        $this->assertNotNull($user->role_id, 'Owner user should have a role_id set');

        // 4) Owner is logged in
        $this->assertAuthenticatedAs($user);

        // 5) Company id stored in session
        $this->assertTrue(
            session()->has('company_id'),
            'company_id should be saved in session after onboarding'
        );

        $companyId = session('company_id');
        $company   = Company::find($companyId);
        $this->assertNotNull($company, 'company_id in session should point to an existing company');
    }
}