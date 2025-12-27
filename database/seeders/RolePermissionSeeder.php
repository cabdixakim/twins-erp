<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // -----------------------------
        // Permissions (slug is the key)
        // -----------------------------
        $permissions = [
            // Settings / Master Data
            ['name' => 'View depots',        'slug' => 'depots.view',        'group' => 'Settings', 'description' => null],
            ['name' => 'Manage depots',      'slug' => 'depots.manage',      'group' => 'Settings', 'description' => null],

            ['name' => 'View suppliers',     'slug' => 'suppliers.view',     'group' => 'Settings', 'description' => null],
            ['name' => 'Manage suppliers',   'slug' => 'suppliers.manage',   'group' => 'Settings', 'description' => null],

            ['name' => 'View transporters',  'slug' => 'transporters.view',  'group' => 'Settings', 'description' => null],
            ['name' => 'Manage transporters','slug' => 'transporters.manage','group' => 'Settings', 'description' => null],

            // Inventory / Depot Stock (future)
            ['name' => 'View inventory',     'slug' => 'inventory.view',     'group' => 'Operations', 'description' => null],
            ['name' => 'Adjust inventory',   'slug' => 'inventory.adjust',   'group' => 'Operations', 'description' => null],

            // Sales (future)
            ['name' => 'Create sales',       'slug' => 'sales.create',       'group' => 'Sales', 'description' => null],
            ['name' => 'Approve sales',      'slug' => 'sales.approve',      'group' => 'Sales', 'description' => null],
            ['name' => 'View sales',         'slug' => 'sales.view',         'group' => 'Sales', 'description' => null],

            // Transport (future)
            ['name' => 'Manage local transport', 'slug' => 'transport.local', 'group' => 'Transport', 'description' => null],
            ['name' => 'Manage intl transport',  'slug' => 'transport.intl',  'group' => 'Transport', 'description' => null],

            // Finance (future)
            ['name' => 'View financials',    'slug' => 'finance.view',       'group' => 'Finance', 'description' => null],
            ['name' => 'Post expenses',      'slug' => 'finance.expense',    'group' => 'Finance', 'description' => null],

            // System / Admin
            ['name' => 'Manage users',       'slug' => 'users.manage',       'group' => 'System', 'description' => null],
            ['name' => 'Manage roles',       'slug' => 'roles.manage',       'group' => 'System', 'description' => null],
        ];

        $permModels = [];
        foreach ($permissions as $perm) {
            $model = Permission::updateOrCreate(
                ['slug' => $perm['slug']],
                [
                    'name'        => $perm['name'],
                    'group'       => $perm['group'],
                    'description' => $perm['description'],
                    'is_active'   => true,
                ]
            );
            $permModels[$model->slug] = $model;
        }

        // -----------------------------
        // Roles (slug is the key)
        // -----------------------------
        $roles = [
            'owner'      => ['name' => 'Owner',      'description' => 'Full access to everything'],
            'manager'    => ['name' => 'Manager',    'description' => 'Oversees operations & finance'],
            'accountant' => ['name' => 'Accountant', 'description' => 'Finance & reporting'],
            'operations' => ['name' => 'Operations', 'description' => 'Depots & stock operations'],
            'transport'  => ['name' => 'Transport',  'description' => 'Local & international transport operations'],
            'viewer'     => ['name' => 'Viewer',     'description' => 'Read-only access'],
        ];

        $roleModels = [];
        foreach ($roles as $slug => $meta) {
            $roleModels[$slug] = Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name'        => $meta['name'],
                    'description' => $meta['description'],
                    'is_active'   => true,
                ]
            );
        }

        // -----------------------------
        // Attach permissions to roles
        // -----------------------------
        // NOTE: Owner bypass is handled in code, so we don't need to sync any permissions for owner.

        $sync = function (string $roleSlug, array $permissionSlugs) use ($roleModels, $permModels) {
            $ids = [];
            foreach ($permissionSlugs as $ps) {
                if (isset($permModels[$ps])) $ids[] = $permModels[$ps]->id;
            }
            $roleModels[$roleSlug]->permissions()->sync($ids);
        };

        $sync('manager', [
            'depots.view','depots.manage',
            'suppliers.view','suppliers.manage',
            'transporters.view','transporters.manage',
            'inventory.view','inventory.adjust',
            'sales.create','sales.approve','sales.view',
            'transport.local','transport.intl',
            'finance.view','finance.expense',
            'users.manage','roles.manage',
        ]);

        $sync('accountant', [
            'finance.view','finance.expense','sales.view',
        ]);

        $sync('operations', [
            'depots.view','depots.manage',
            'inventory.view','inventory.adjust',
            'sales.create','sales.view',
        ]);

        $sync('transport', [
            'transport.local','transport.intl',
        ]);

        $sync('viewer', [
            'depots.view',
            'suppliers.view',
            'transporters.view',
            'inventory.view',
            'sales.view',
            'finance.view',
        ]);
    }
}