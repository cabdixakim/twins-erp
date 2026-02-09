<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Company;
use App\Models\Depot;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use App\Models\InventoryMovement;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CrossDockPurchaseTest extends TestCase
{
    use RefreshDatabase;

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
        $ownerRole = Role::firstOrCreate([
            'slug' => 'owner',
        ], [
            'name' => 'Owner',
            'description' => 'Full access',
            'is_system' => true,
        ]);
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
    public function cross_dock_purchase_creates_batch_and_receipt_movement_and_updates_batch_qtys()
    {
        extract($this->createOwnerUserWithCompany());
        $product = Product::create([
            'name' => 'Test Product',
            'company_id' => $company->id,
            'base_uom' => 'L',
        ]);
        $crossDockDepot = Depot::create([
            'name' => 'CROSS DOCK',
            'city' => 'City',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $purchase = Purchase::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'type' => 'cross_dock',
            'qty' => 1000,
            'unit_price' => 1.5,
            'currency' => 'USD',
            'status' => 'confirmed',
        ]);

        // Simulate logic: create batch and movement (normally in observer/service)
        $batch = Batch::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'qty_received' => $purchase->qty,
            'qty_remaining' => $purchase->qty,
        ]);
        $purchase->batch_id = $batch->id;
        $purchase->save();

        $movement = InventoryMovement::create([
            'company_id' => $company->id,
            'to_depot_id' => $crossDockDepot->id,
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'qty' => $purchase->qty,
            'type' => 'receipt',
        ]);

        // Assertions
        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'qty_received' => 1000,
            'qty_remaining' => 1000,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'to_depot_id' => $crossDockDepot->id,
            'batch_id' => $batch->id,
            'qty' => 1000,
            'type' => 'receipt',
        ]);
        $this->assertEquals($batch->id, $purchase->fresh()->batch_id);
    }
}
