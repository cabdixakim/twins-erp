<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $existing = DB::table('roles')->pluck('slug')->all();

        $roles = [
            [
                'name'        => 'Owner',
                'slug'        => 'owner',
                'description' => 'Company owner. Full god-mode access including billing and company settings.',
                'is_system'   => true,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Admin',
                'slug'        => 'admin',
                'description' => 'Full operational access; cannot delete the company or manage billing.',
                'is_system'   => true,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Manager',
                'slug'        => 'manager',
                'description' => 'Manages purchases, sales, and client accounts. Cannot access admin settings.',
                'is_system'   => true,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Accountant',
                'slug'        => 'accountant',
                'description' => 'Full view access + can record payments, credit notes, and mark invoices paid. Cannot post purchases or sales.',
                'is_system'   => true,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Transport Controller',
                'slug'        => 'transport-controller',
                'description' => 'Manages import nominations, truck logistics, and cross-dock dispatches.',
                'is_system'   => true,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Viewer',
                'slug'        => 'viewer',
                'description' => 'Read-only access across all modules. Cannot post, confirm, or record anything.',
                'is_system'   => true,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        foreach ($roles as $role) {
            if (!in_array($role['slug'], $existing)) {
                DB::table('roles')->insert($role);
            }
        }
    }

    public function down(): void
    {
        DB::table('roles')
            ->whereIn('slug', ['owner', 'admin', 'manager', 'accountant', 'transport-controller', 'viewer'])
            ->delete();
    }
};
