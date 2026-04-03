# Twins ERP

A specialized Enterprise Resource Planning (ERP) system for the **Fuel & Transport** industry, built with Laravel 11.

## Architecture

- **Backend**: PHP 8.2 + Laravel 11 (Blade templating, Eloquent ORM)
- **Frontend**: Tailwind CSS 4.0 + Vite 5 (asset bundling, no JS framework)
- **Database**: PostgreSQL (Replit built-in)
- **PHP Dependencies**: Composer
- **JS Dependencies**: npm

## Features

- Multi-tenant (multi-company) platform
- Inventory management with FIFO costing across depots
- Purchase workflow (import, local_depot, cross_dock)
- Sales & logistics management with delivery tracking
- Role-based access control (RBAC)

## Project Structure

```
app/
  Http/Controllers/   # Domain controllers
  Models/             # Eloquent models (Company, Batch, Depot, Sale, Purchase...)
  Services/           # Business logic (InventoryLedger, DepotService)
  Providers/          # Service providers
database/
  migrations/         # Schema history (renamed to consistent YYYY_MM_DD format)
  seeders/            # Role/permission + demo data seeders
resources/
  views/              # Blade templates (admin, auth, dashboard, products, purchases, sales)
  css/js/             # Frontend assets (Tailwind CSS + vanilla JS)
routes/
  web.php             # Web routes with auth/company middleware groups
public/build/         # Compiled assets (Vite output)
```

## Development Setup

The app runs on **port 5000** via `php artisan serve --host=0.0.0.0 --port=5000`.

### Database

PostgreSQL is configured via Replit's built-in database. Credentials are injected via environment secrets (PGHOST, PGPORT, etc.).

Migration filenames were normalized to consistent `YYYY_MM_DD` zero-padded format to fix sort-order issues.

### Frontend Assets

Built with Vite. In development the assets are pre-built. To rebuild:
```bash
chmod +x node_modules/.bin/vite
node_modules/.bin/vite build
```

### Important Notes

- `app/Http/Controllers/CompanySettingsController.php` — class name is `CompanyController` (PSR-4 mismatch, non-critical)
- `app/Http/Middleware/RoleMiddleWare.php` — class name is `RoleMiddleware` (PSR-4 mismatch, non-critical)
- Demo data is seeded via `DemoDataSeeder` and `RolePermissionSeeder`

## Environment

- `APP_ENV=local`, `APP_DEBUG=true`
- `DB_CONNECTION=pgsql` pointing to Replit helium PostgreSQL
- `SESSION_DRIVER=file`, `CACHE_DRIVER=file`
