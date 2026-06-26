---
name: Permission middleware routing
description: All routes now use permission: middleware; role: gates removed; how default permissions are seeded per role.
---

## Rule
Every protected route uses `->middleware('permission:X')` — NOT `role:Y,Z`.
Owner and admin bypass all permission checks in PermissionMiddleware automatically.
Do NOT add `role:` middleware to routes — it has been fully replaced.

## Default role permissions
Seeded via `database/seeders/RolePermissionSeeder.php`. Run with:
  `php artisan db:seed --class=RolePermissionSeeder`

| Role | Count | Key permissions |
|---|---|---|
| manager | 40 | Full operational, no void |
| accountant | 22 | Ledgers, posting, petty-cash |
| transport-controller | 12 | Import logistics, transporter |
| viewer | 8 | *.view only |

## Admin group
`permission:admin.users` on the outer group → accessible only to owner/admin (no one else has admin.users).
Role management routes add `permission:admin.roles` on top.
Audit log moved OUT of admin group — gated with `permission:purchases.view` (any manager+ role).

## Why
Role middleware (`role:owner,admin,manager`) was coarse and not configurable by admins at runtime.
Permission middleware allows fine-grained control assignable via /admin/roles UI.

## How to add new permissions
1. Insert row into `permissions` table (slug, name, group)
2. Add `->middleware('permission:new.slug')` to the route
3. Update RolePermissionSeeder with the new slug for relevant roles
4. Re-run seeder: `php artisan db:seed --class=RolePermissionSeeder`
