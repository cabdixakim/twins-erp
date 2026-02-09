<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Transporter;
use App\Models\Depot;
use App\Models\Purchase;
use App\Models\Role;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create 2 more companies
        $companies = Company::factory()->count(2)->create();

        // Include the very first company (already created)
        $firstCompany = Company::orderBy('id')->first();
        if ($firstCompany) {
            $companies = $companies->prepend($firstCompany);
        }

        // Create 3 users with different roles (owner, manager, accountant)
        $roles = [
            'owner' => Role::where('slug', 'owner')->first(),
            'manager' => Role::where('slug', 'manager')->first(),
            'accountant' => Role::where('slug', 'accountant')->first(),
        ];
        $users = collect();
        foreach ($roles as $slug => $role) {
            $email = $slug . '@demo.test';
            $user = User::where('email', $email)->first();
            if (!$user) {
                $users->push(User::factory()->create([
                    'role_id' => $role->id,
                    'email' => $email,
                ]));
            } else {
                $users->push($user);
            }
        }

        // For each company, create demo data and purchases
        $allPurchases = 0;
        $totalPurchases = 10;
        foreach ($companies as $company) {
            // 2 suppliers
            $suppliers = Supplier::factory()->count(2)->create(['company_id' => $company->id]);
            // 2 transporters
            Transporter::factory()->count(2)->create(['company_id' => $company->id]);
            // 2 depots
            $depots = Depot::factory()->count(2)->create(['company_id' => $company->id]);
            // 2 products
            $products = \App\Models\Product::factory()->count(2)->create(['company_id' => $company->id]);

            // Create a share of purchases for this company
            $purchasesForCompany = intdiv($totalPurchases, count($companies));
            if ($allPurchases + $purchasesForCompany > $totalPurchases) {
                $purchasesForCompany = $totalPurchases - $allPurchases;
            }
            for ($i = 0; $i < $purchasesForCompany; $i++) {
                Purchase::factory()->create([
                    'company_id'   => $company->id,
                    'supplier_id'  => $suppliers->random()->id,
                    'depot_id'     => $depots->random()->id,
                    'product_id'   => $products->random()->id,
                ]);
                $allPurchases++;
            }
        }
    }
}
