<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ── 1. Define all permissions ────────────────────────────────────
        $permissions = [

            // Purchases
            ['slug' => 'purchases.view',                 'name' => 'View purchases',                  'group' => 'Purchases',   'description' => 'See the purchases list and detail pages'],
            ['slug' => 'purchases.create',               'name' => 'Create purchases',                'group' => 'Purchases',   'description' => 'Draft new purchase orders'],
            ['slug' => 'purchases.confirm',              'name' => 'Confirm purchases',               'group' => 'Purchases',   'description' => 'Confirm a draft purchase order'],
            ['slug' => 'purchases.receive',              'name' => 'Receive into depot',              'group' => 'Purchases',   'description' => 'Mark a local-depot purchase as received'],
            ['slug' => 'purchases.undo-receipt',         'name' => 'Undo depot receipt',              'group' => 'Purchases',   'description' => 'Reverse a depot receipt back to confirmed'],
            ['slug' => 'purchases.cancel',               'name' => 'Cancel purchases',                'group' => 'Purchases',   'description' => 'Cancel a draft, confirmed or nominated purchase'],
            ['slug' => 'purchases.void',                 'name' => 'Void / return to seller',         'group' => 'Purchases',   'description' => 'Void a received local-depot purchase'],
            ['slug' => 'purchases.cross-dock-transfer',  'name' => 'Transfer cross-dock stock',       'group' => 'Purchases',   'description' => 'Move cross-dock stock into a target depot'],
            ['slug' => 'purchases.cross-dock-dispatch',  'name' => 'Dispatch cross-dock to client',   'group' => 'Purchases',   'description' => 'Dispatch cross-dock stock out to a client'],
            ['slug' => 'purchases.import-nominations',   'name' => 'Manage import logistics',         'group' => 'Purchases',   'description' => 'Add trucks, record loads, deliveries and border clearance'],
            ['slug' => 'purchases.batch-costs',          'name' => 'Manage landed costs',             'group' => 'Purchases',   'description' => 'Add or remove freight/duty/border costs on a batch'],

            // Sales
            ['slug' => 'sales.view',                     'name' => 'View sales',                      'group' => 'Sales',       'description' => 'See the sales list and detail pages'],
            ['slug' => 'sales.create',                   'name' => 'Create sales',                    'group' => 'Sales',       'description' => 'Draft new sales orders'],
            ['slug' => 'sales.edit',                     'name' => 'Edit sales',                      'group' => 'Sales',       'description' => 'Edit a pending sales order'],
            ['slug' => 'sales.post',                     'name' => 'Post sales',                      'group' => 'Sales',       'description' => 'Post a sale to the inventory ledger'],

            // Clients
            ['slug' => 'clients.view',                   'name' => 'View clients',                    'group' => 'Clients',     'description' => 'See the client list and profiles'],
            ['slug' => 'clients.create',                 'name' => 'Create clients',                  'group' => 'Clients',     'description' => 'Add new clients'],
            ['slug' => 'clients.edit',                   'name' => 'Edit clients',                    'group' => 'Clients',     'description' => 'Update client details'],
            ['slug' => 'clients.delete',                 'name' => 'Delete clients',                  'group' => 'Clients',     'description' => 'Remove a client (blocked if they have dispatches)'],

            // Suppliers
            ['slug' => 'suppliers.view',                 'name' => 'View suppliers & ledger',         'group' => 'Suppliers',   'description' => 'See the supplier list, ledger entries and statements'],
            ['slug' => 'suppliers.payments',             'name' => 'Record supplier payments',        'group' => 'Suppliers',   'description' => 'Post a payment against a supplier balance'],
            ['slug' => 'suppliers.credits',              'name' => 'Record supplier credit notes',    'group' => 'Suppliers',   'description' => 'Post a credit note against a supplier balance'],
            ['slug' => 'suppliers.manage',               'name' => 'Create / edit suppliers',         'group' => 'Suppliers',   'description' => 'Add new suppliers and update their details'],

            // Transporters
            ['slug' => 'transporters.view',              'name' => 'View transporters & ledger',      'group' => 'Transporters','description' => 'See the transporter list, ledger and statements'],
            ['slug' => 'transporters.payments',          'name' => 'Record transporter payments',     'group' => 'Transporters','description' => 'Post a payment against a transporter balance'],
            ['slug' => 'transporters.charges',           'name' => 'Record transporter charges',      'group' => 'Transporters','description' => 'Post advances and charges to a transporter ledger'],
            ['slug' => 'transporters.manage',            'name' => 'Create / edit transporters',      'group' => 'Transporters','description' => 'Add new transporters and update their details'],

            // Depots
            ['slug' => 'depots.view',                    'name' => 'View depots & ledger',            'group' => 'Depots',      'description' => 'See depot list, stock, ledger entries and statements'],
            ['slug' => 'depots.charges',                 'name' => 'Record depot charges',            'group' => 'Depots',      'description' => 'Post storage, offloading and loading charges'],
            ['slug' => 'depots.payments',                'name' => 'Record depot payments',           'group' => 'Depots',      'description' => 'Post a payment against a depot balance'],
            ['slug' => 'depots.manage',                  'name' => 'Create / edit depots',            'group' => 'Depots',      'description' => 'Add new depots and update their details'],

            // Inventory
            ['slug' => 'inventory.view',                 'name' => 'View inventory & stock',          'group' => 'Inventory',   'description' => 'See depot stock levels and inventory movements'],
            ['slug' => 'inventory.adjust',               'name' => 'Post inventory adjustments',      'group' => 'Inventory',   'description' => 'Create manual stock adjustments'],
            ['slug' => 'inventory.periods',              'name' => 'Manage inventory periods',        'group' => 'Inventory',   'description' => 'Open, close and pause accounting periods'],

            // Petty Cash
            ['slug' => 'petty-cash.view',                'name' => 'View petty cash',                 'group' => 'Petty Cash',  'description' => 'See petty cash accounts and transactions'],
            ['slug' => 'petty-cash.transact',            'name' => 'Record petty cash transactions',  'group' => 'Petty Cash',  'description' => 'Post expenditure and replenishments'],
            ['slug' => 'petty-cash.manage',              'name' => 'Manage petty cash accounts',      'group' => 'Petty Cash',  'description' => 'Create and configure petty cash float accounts'],

            // Reports
            ['slug' => 'reports.export',                 'name' => 'Export data',                     'group' => 'Reports',     'description' => 'Download the full data export ZIP'],

            // Settings
            ['slug' => 'settings.company',               'name' => 'Edit company settings',           'group' => 'Settings',    'description' => 'Update company name, logo and configuration'],
            ['slug' => 'settings.products',              'name' => 'Manage products',                 'group' => 'Settings',    'description' => 'Create, edit and toggle products'],
            ['slug' => 'settings.inventory',             'name' => 'Inventory settings',              'group' => 'Settings',    'description' => 'Change costing method and manage periods'],

            // Admin
            ['slug' => 'admin.users',                    'name' => 'Manage users',                    'group' => 'Admin',       'description' => 'Invite, edit and deactivate user accounts'],
            ['slug' => 'admin.roles',                    'name' => 'Manage roles & permissions',      'group' => 'Admin',       'description' => 'Create roles and configure permission sets'],
        ];

        // Upsert so re-running is safe
        foreach ($permissions as $p) {
            DB::table('permissions')->upsert(
                array_merge($p, ['created_at' => $now, 'updated_at' => $now]),
                ['slug'],
                ['name', 'group', 'description', 'updated_at']
            );
        }

        // ── 2. Assign to roles ───────────────────────────────────────────
        $allSlugs   = array_column($permissions, 'slug');
        $permMap    = DB::table('permissions')->pluck('id', 'slug'); // slug => id
        $roleMap    = DB::table('roles')->pluck('id', 'slug');       // slug => id

        $assignments = [

            'owner' => $allSlugs, // full access

            'admin' => $allSlugs, // full access (minus nothing)

            'manager' => [
                'purchases.view','purchases.create','purchases.confirm',
                'purchases.receive','purchases.undo-receipt','purchases.cancel',
                'purchases.cross-dock-transfer','purchases.cross-dock-dispatch',
                'purchases.import-nominations','purchases.batch-costs',
                'sales.view','sales.create','sales.edit','sales.post',
                'clients.view','clients.create','clients.edit',
                'suppliers.view','transporters.view','depots.view',
                'inventory.view','petty-cash.view','petty-cash.transact',
                'reports.export','settings.products',
            ],

            'accountant' => [
                'purchases.view','sales.view','clients.view',
                'suppliers.view','suppliers.payments','suppliers.credits',
                'transporters.view','transporters.payments','transporters.charges',
                'depots.view','depots.charges','depots.payments',
                'inventory.view',
                'petty-cash.view','petty-cash.transact','petty-cash.manage',
                'reports.export',
            ],

            'transport-controller' => [
                'purchases.view','purchases.import-nominations',
                'purchases.cross-dock-transfer','purchases.cross-dock-dispatch',
                'transporters.view','transporters.charges',
                'depots.view','inventory.view',
            ],

            'viewer' => [
                'purchases.view','sales.view','clients.view',
                'suppliers.view','transporters.view','depots.view',
                'inventory.view','petty-cash.view',
            ],
        ];

        // Pivot table: role_permission (role_id, permission_id)
        foreach ($assignments as $roleSlug => $permSlugs) {
            $roleId = $roleMap[$roleSlug] ?? null;
            if (!$roleId) continue;

            $rows = [];
            foreach ($permSlugs as $slug) {
                $permId = $permMap[$slug] ?? null;
                if ($permId) $rows[] = ['role_id' => $roleId, 'permission_id' => $permId];
            }

            if (empty($rows)) continue;

            // Delete existing then re-insert cleanly
            DB::table('role_permission')->where('role_id', $roleId)->delete();
            DB::table('role_permission')->insert($rows);
        }

        $this->command->info('Permissions seeded: '.count($permissions).' permissions assigned to '.count($assignments).' roles.');
    }
}
