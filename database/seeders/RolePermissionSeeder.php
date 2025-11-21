<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Define permissions ----
        $permissions = [
            // Depots & inventory
            ['name' => 'View depots',          'slug' => 'depots.view',          'module' => 'depots'],
            ['name' => 'Manage depots',        'slug' => 'depots.manage',        'module' => 'depots'],
            ['name' => 'View inventory',       'slug' => 'inventory.view',       'module' => 'inventory'],
            ['name' => 'Adjust inventory',     'slug' => 'inventory.adjust',     'module' => 'inventory'],

            // Sales
            ['name' => 'Create sales',         'slug' => 'sales.create',         'module' => 'sales'],
            ['name' => 'Approve sales',        'slug' => 'sales.approve',        'module' => 'sales'],
            ['name' => 'View sales',           'slug' => 'sales.view',           'module' => 'sales'],

            // Transport
            ['name' => 'Manage local transport',  'slug' => 'transport.local',   'module' => 'transport'],
            ['name' => 'Manage intl transport',   'slug' => 'transport.intl',    'module' => 'transport'],

            // Finance
            ['name' => 'View financials',      'slug' => 'finance.view',         'module' => 'finance'],
            ['name' => 'Post expenses',        'slug' => 'finance.expense',      'module' => 'finance'],

            // Users / roles
            ['name' => 'Manage users',         'slug' => 'users.manage',         'module' => 'system'],
            ['name' => 'Manage roles',         'slug' => 'roles.manage',         'module' => 'system'],
        ];

        $permModels = [];
        foreach ($permissions as $perm) {
            $permModels[$perm['slug']] = Permission::firstOrCreate(
                ['slug' => $perm['slug']],
                $perm
            );
        }

        // ---- Define roles ----
        $roles = [
            'owner'      => ['name' => 'Owner',      'description' => 'Full access to everything',          'is_system' => true],
            'manager'    => ['name' => 'Manager',    'description' => 'Oversees operations & finance',      'is_system' => true],
            'accountant' => ['name' => 'Accountant', 'description' => 'Finance & reporting',                'is_system' => true],
            'operations' => ['name' => 'Operations', 'description' => 'Depots & loads management',         'is_system' => true],
            'transport'  => ['name' => 'Transport',  'description' => 'Local & intl transport operations',  'is_system' => true],
            'viewer'     => ['name' => 'Viewer',     'description' => 'Read-only access',                  'is_system' => true],
        ];

        $roleModels = [];
        foreach ($roles as $slug => $meta) {
            $roleModels[$slug] = Role::firstOrCreate(
                ['slug' => $slug],
                array_merge($meta, ['slug' => $slug])
            );
        }

        // ---- Attach permissions to roles ----

        // owner gets everything by rule in User::hasPermission(), so we don't need attachments

        // Manager
        $roleModels['manager']->permissions()->sync([
            $permModels['depots.view']->id,
            $permModels['depots.manage']->id,
            $permModels['inventory.view']->id,
            $permModels['inventory.adjust']->id,
            $permModels['sales.create']->id,
            $permModels['sales.approve']->id,
            $permModels['sales.view']->id,
            $permModels['transport.local']->id,
            $permModels['transport.intl']->id,
            $permModels['finance.view']->id,
            $permModels['finance.expense']->id,
            $permModels['users.manage']->id,
            $permModels['roles.manage']->id,
        ]);

        // Accountant
        $roleModels['accountant']->permissions()->sync([
            $permModels['finance.view']->id,
            $permModels['finance.expense']->id,
            $permModels['sales.view']->id,
        ]);

        // Operations
        $roleModels['operations']->permissions()->sync([
            $permModels['depots.view']->id,
            $permModels['depots.manage']->id,
            $permModels['inventory.view']->id,
            $permModels['inventory.adjust']->id,
            $permModels['sales.create']->id,
            $permModels['sales.view']->id,
        ]);

        // Transport
        $roleModels['transport']->permissions()->sync([
            $permModels['transport.local']->id,
            $permModels['transport.intl']->id,
        ]);

        // Viewer
        $roleModels['viewer']->permissions()->sync([
            $permModels['depots.view']->id,
            $permModels['inventory.view']->id,
            $permModels['sales.view']->id,
            $permModels['finance.view']->id,
        ]);
    }
}