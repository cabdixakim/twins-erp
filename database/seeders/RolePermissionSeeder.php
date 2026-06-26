<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Map: role slug => permission slugs it gets by default
        $matrix = [

            'manager' => [
                // Purchases — full operational access (no void)
                'purchases.view', 'purchases.create', 'purchases.confirm',
                'purchases.receive', 'purchases.undo-receipt', 'purchases.cancel',
                'purchases.cross-dock-transfer', 'purchases.cross-dock-dispatch',
                'purchases.import-nominations', 'purchases.batch-costs',
                // Sales — full
                'sales.view', 'sales.create', 'sales.edit', 'sales.post',
                // Clients
                'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
                // Suppliers
                'suppliers.view', 'suppliers.manage', 'suppliers.payments', 'suppliers.credits',
                // Depots
                'depots.view', 'depots.manage', 'depots.charges', 'depots.payments',
                // Transporters
                'transporters.view', 'transporters.manage',
                'transporters.charges', 'transporters.payments',
                // Inventory
                'inventory.view', 'inventory.adjust', 'inventory.periods',
                // Petty cash
                'petty-cash.view', 'petty-cash.transact', 'petty-cash.manage',
                // Reports
                'reports.export',
                // Settings
                'settings.company', 'settings.products', 'settings.inventory',
            ],

            'accountant' => [
                // Purchases — view + landed costs only
                'purchases.view', 'purchases.batch-costs',
                // Sales — view + posting + edit (needed for invoicing)
                'sales.view', 'sales.edit', 'sales.post',
                // Clients
                'clients.view',
                // Suppliers — view + record payments + credits
                'suppliers.view', 'suppliers.payments', 'suppliers.credits',
                // Depots — view + charges + payments
                'depots.view', 'depots.charges', 'depots.payments',
                // Transporters — view + charges + payments
                'transporters.view', 'transporters.charges', 'transporters.payments',
                // Inventory — view + period management
                'inventory.view', 'inventory.periods',
                // Petty cash — full
                'petty-cash.view', 'petty-cash.transact', 'petty-cash.manage',
                // Reports
                'reports.export',
                // Settings
                'settings.inventory',
            ],

            'transport-controller' => [
                // Purchases — view + import logistics only
                'purchases.view', 'purchases.import-nominations',
                // Sales — view
                'sales.view',
                // Clients — view
                'clients.view',
                // Suppliers — view
                'suppliers.view',
                // Depots — view
                'depots.view',
                // Transporters — operational
                'transporters.view', 'transporters.manage', 'transporters.charges',
                // Inventory — view
                'inventory.view',
                // Petty cash — view
                'petty-cash.view',
                // Reports — export
                'reports.export',
            ],

            'viewer' => [
                'purchases.view',
                'sales.view',
                'clients.view',
                'suppliers.view',
                'depots.view',
                'transporters.view',
                'inventory.view',
                'petty-cash.view',
            ],
        ];

        foreach ($matrix as $roleSlug => $permSlugs) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            if (! $roleId) {
                $this->command->warn("Role '{$roleSlug}' not found — skipping.");
                continue;
            }

            $permIds = DB::table('permissions')
                ->whereIn('slug', $permSlugs)
                ->pluck('id')
                ->toArray();

            // Sync (replaces whatever is there)
            DB::table('role_permission')->where('role_id', $roleId)->delete();
            $rows = array_map(fn($pid) => [
                'role_id'       => $roleId,
                'permission_id' => $pid,
                'created_at'    => now(),
                'updated_at'    => now(),
            ], $permIds);
            DB::table('role_permission')->insert($rows);

            $this->command->info("  {$roleSlug}: assigned " . count($permIds) . " permissions.");
        }
    }
}
