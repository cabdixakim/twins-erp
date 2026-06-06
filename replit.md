# Twins ERP — Project State Document

> **Fuel & Transport ERP** — Laravel 11 / PHP 8.2 / PostgreSQL / Tailwind CSS 4 / Vite 5
> Running on Replit, port 5000, proxied via mTLS iframe.
> Active user: `kim@twins.com`

---

## Architecture

| Layer | Stack |
|---|---|
| Backend | PHP 8.2, Laravel 11 (Blade, Eloquent, artisan) |
| Frontend | Tailwind CSS 4.0 (CDN in dev, Vite-built for prod) + vanilla JS |
| Database | PostgreSQL (Replit built-in, credentials via env secrets) |
| Session/Cache | File driver |
| Auth | Custom `AuthController` (no Breeze/Jetstream) |
| Asset URL | `ASSET_URL=/` in `.env` + `asset_url` key in `config/app.php` — never remove |

---

## Completed Milestones

### Phase 1 — Inventory Foundation ✅
- **Inventory periods** (`inventory_periods` table, `InventoryPeriod` model)
  - Auto-creates Period 1 on first posting via `PeriodResolver`
  - Supports `weighted_average` (default) and `specific_lot` costing
- **PostingGate** (`app/Services/PostingGate.php`) — single choke point; blocks if no open period or period paused
- **PeriodResolver** (`app/Services/PeriodResolver.php`) — resolves or auto-creates open period
- **InventoryLedger** (`app/Services/InventoryLedger.php`) — fully rewritten, period-aware
  - `receipt(array $data, array $idempotencyWhere)` — updates depot stock + batch, recalculates weighted avg
  - `issue(array $data, array $idempotencyWhere)` — FIFO allocation, creates `inventory_consumptions`
- **BootstrapInventoryPeriods** artisan command — created for initial setup
- **Inventory Settings page** at `/settings/inventory` — view/change costing method, open/close periods
- Migrations: costing fields on `companies`, `inventory_periods`, `period_id` on movements/consumptions

### Phase 2 — Purchase Workflows (Local Depot + Cross-Dock) ✅

#### Local Depot (`type = local_depot`)
State machine: `draft → confirmed → received`
- **Create**: reference auto-generated as `PO-{CODE}-{YEAR}-{SEQ}` if blank; duplicate reference blocked
- **Confirm**: locks draft, creates/attaches a Batch, gates through PostingGate
- **Receive into depot**: posts `receipt` movement via InventoryLedger; updates depot_stock + batch
- **Undo Receipt** *(new)*: reverses the receipt movement, restores depot_stock and batch qty, returns to `confirmed`

#### Cross-Dock (`type = cross_dock`)
State machine: `draft → confirmed → transferred | dispatched`
- **Confirm**: locks draft, creates Batch, stock goes into virtual `CROSS DOCK` depot (`is_system=true`, auto-created via `getOrCreateCrossDockDepotId()`)
- **Transfer to depot** *(new)*: modal with depot dropdown + qty + note; posts issue from CROSS DOCK + receipt into target depot; status → `transferred`; `depot_id` updated to target
- **Dispatch out** *(new)*: modal with qty + note; posts issue from CROSS DOCK (stock exits); status → `dispatched`

#### Import (`type = import`) — full logistics pipeline ✅
State machine: `draft → confirmed → nominated → received`

**Import Logistics Pipeline** (truck-level tracking via `ImportNominationController`):
- **Nomination setup** — transporter, rate/1000L, allowed loss %, short-charge rate, advances
- **Add trucks** — truck reg, trailer reg, driver (name/passport/license/phone), capacity
- **Record load** — qty loaded + pickup date + terminal; truck moves to `loaded`
- **Mark loading failed** — records reason; capacity counted as remaining at shipper
- **Mark in transit** — one-click; truck moves to `in_transit`
- **DRC border clearance** — TR8 number, T1 number, border date; truck moves to `border_cleared`
- **Record delivery** — depot, qty delivered, date; auto-calculates shortfall:
  - `shortfall_qty = max(0, qty_loaded - qty_delivered)`
  - `allowed_loss = qty_loaded × allowed_loss_pct / 100` (default 0.3% AGO / 0.5% PMS)
  - `excess_loss = max(0, shortfall_qty - allowed_loss)`
  - `shortfall_charge = excess_loss × short_charge_rate / 1000`
- **Remaining at shipper** = purchase qty − total qty loaded
- **Financial summary** = gross (loaded × rate/1000) − advances − shortfall charges = net payable

**New tables**: `import_nominations`, `import_trucks`
**New models**: `ImportNomination`, `ImportTruck`
**New controller**: `app/Http/Controllers/ImportNominationController.php` (9 route actions)
**New partial**: `resources/views/purchases/_import_logistics.blade.php`

#### Purchase lifecycle actions (all in PurchaseController)
- **Edit** (`GET /purchases/{id}/edit`, `PATCH /purchases/{id}`): edit any field on a draft. Type is locked.
- **Cancel** (`POST /purchases/{id}/cancel`): available for draft, confirmed, nominated. For cross_dock confirmed: auto-reverses the CROSS DOCK receipt. Reason field optional.
- **Void / Return to seller** (`POST /purchases/{id}/void`): available for received local_depot only. Reverses the depot receipt movement, reduces batch qty, marks status `voided`.

#### Status colours (both list + detail views)
| Status | Colour |
|---|---|
| draft | grey |
| confirmed | emerald |
| nominated | amber |
| received | emerald (solid) |
| transferred | sky |
| dispatched | purple |
| cancelled | rose |
| voided | rose-dark |

#### New fields on `purchases` table (migration `2026_04_03_000005`)
`actioned_at`, `actioned_by`, `action_note`

---

### Phase 3 — Design Overhaul ✅
- **CSS palette** (`resources/css/app.css`): fixed light mode `--tw-accent-soft` (was wrong green, now `rgba(16,185,129,.12)`); light bg `#f4f6fb`; dark navy remains. Removed duplicate animation block. Cleaner borders/shadows.
- **Sidebar navigation**: all emoji icons replaced with inline SVGs in `nav-settings.blade.php`. Verbose kicker sub-labels removed from `nav-primary.blade.php`.
- **Purchase show.blade.php**: new Edit/Cancel/Void buttons; Cancel modal + Void modal; cleaner header subtitle; removed "Tip:" idempotency note; buttons use SVG icons not text glyphs.
- **edit.blade.php**: new view — clone of create adapted for PATCH editing of drafts. Type is shown as read-only badge, not changeable.

### Phase 5 — Pre-Sales Ledgers ✅

#### Supplier Ledger
- **`supplier_ledger_entries` table** — `purchase_invoice | payment | credit_note | adjustment` entries; positive = owed to supplier
- **`SupplierLedgerEntry` model** — standard timestamps, polymorphic `ref_type/ref_id`
- **`SupplierLedgerController`** — `index`, `show`, `recordPayment`, `recordCredit`, `statement`, `exportCsv` + static `postInvoice()` helper
- **Auto-posting**: local_depot → invoice posted on `receive()`; cross_dock → invoice posted on `confirm()`; import → invoice posted per truck on `recordDelivery()` (proportional: qty_delivered × unit_price)
- **Views**: `suppliers/index.blade.php`, `suppliers/show.blade.php`, `suppliers/statement.blade.php` (printable)
- **Routes**: `/suppliers`, `/suppliers/{supplier}`, `/suppliers/{supplier}/payments`, `/suppliers/{supplier}/credits`, `/suppliers/{supplier}/statement`, `/suppliers/{supplier}/export`
- **Nav**: "Suppliers" added to primary nav (between Transporters and Depots)
- **Dashboard**: Supplier Payables KPI card (rose colour, top-3 breakdown)

#### Depot Charges Ledger
- **`depots` table extended** — `default_currency`, `contact_person`, `phone` columns added
- **`depot_ledger_entries` table** — `storage_charge | throughput_charge | loading_fee | other_charge | payment | adjustment`
- **`DepotLedgerEntry` model** — standard polymorphic ledger entry
- **`DepotLedgerController`** — `index`, `show`, `recordCharge`, `recordPayment`, `statement`, `exportCsv`
- **Views**: `depots/index.blade.php`, `depots/show.blade.php`, `depots/statement.blade.php` (printable)
- **Routes**: `/depots`, `/depots/{depot}`, `/depots/{depot}/charges`, `/depots/{depot}/payments`, `/depots/{depot}/statement`, `/depots/{depot}/export`
- **Nav**: "Depots" added to primary nav (after Suppliers)
- **Dashboard**: Depot Payables KPI card (purple colour, top-3 breakdown)

#### Batch Costs (Landed Costs) UI
- **`BatchCostController`** — `store` + `destroy`; validates category (freight/duty/border_charge/hospitality/storage/penalty/other), amount, currency, exchange_rate, entry_date
- **Routes**: `POST /purchases/{purchase}/batch-costs`, `DELETE /purchases/{purchase}/batch-costs/{batchCost}`
- **Purchase show page**: "Landed Costs" section (visible once confirmed); "Add cost" modal inline; table with category badge, description, amount/currency, exchange rate; total base-currency footer; per-row delete

### Phase 4 — Clients Module ✅
- **`clients` table**: `company_id`, `name`, `code`, `type`, `country`, `city`, `contact_person`, `phone`, `email`, `currency`, `credit_limit`, `is_active`, `notes`
- **`purchases.client_id`**: nullable FK to `clients` — set when a cross-dock purchase is dispatched
- **`app/Models/Client.php`**: uses `BelongsToActiveCompany`, `$guarded = []`, `client()` relationship on Purchase
- **`ClientController`**: full CRUD (index/create/store/show/edit/update/destroy). Duplicate name check per company. Blocks delete if dispatches exist.
- **Views**: `clients/index.blade.php` (paginated table + filters), `clients/create.blade.php` (create + edit), `clients/show.blade.php` (info + recent dispatches)
- **Nav**: "Clients" added to primary nav (after Sales), `$onClients` flag in `app.blade.php`, passed through both desktop + mobile sidebars
- **Dispatch modal** (`purchases/show.blade.php`): Client dropdown added with link to create-new. `crossDockDispatch()` stores `client_id` + includes client name in movement notes.

---

## Key Architectural Rules

1. **All inventory postings MUST go through `PostingGate::assertCanPost()`** — throws `RuntimeException` if no open period or period is paused.
2. **Weighted average costing** recalculates after every receipt and propagates to all `depot_stock` rows for that (company, product, depot).
3. **Idempotency** — both `InventoryLedger::receipt()` and `::issue()` accept an `$idempotencyWhere` array; if a matching movement already exists it returns that instead of double-posting.
4. **CROSS DOCK depot** is a system depot (`is_system=true`) — excluded from all user-facing depot dropdowns; auto-created on first cross-dock confirm.
5. **`ASSET_URL=/`** must stay in `.env` + `asset_url` in `config/app.php` — assets use root-relative paths to work through Replit's proxy.
6. **PSR-4 quirks** (non-breaking): `CompanySettingsController.php` has class `CompanyController`; `RoleMiddleWare.php` has class `RoleMiddleware`.
7. **`bootstrap/app.php`** uses old Laravel 10 singleton Kernel pattern — not Laravel 11 style.
8. **Console\Kernel** uses `$this->load(__DIR__.'/Commands')` for command discovery.
9. **DemoDataSeeder has been deleted** — do not re-add.

---

## Database Tables (complete map)

> Full schema detail: `docs/schema-roadmap.md`

### Core
| Table | Purpose |
|---|---|
| `companies` | Multi-tenant anchor; `costing_method`, `accounting_enabled`, `inventory_periods_enabled` |
| `users` | Auth; `active_company_id` FK |
| `company_user` | M2M users ↔ companies |

### Inventory
| Table | Purpose |
|---|---|
| `products` | Fuel products per company; `allowed_loss_pct` for import shortfall default |
| `depots` | Physical depots + CROSS DOCK system depot (`is_system=true`) |
| `depot_stocks` | Running stock by (company, depot, product, batch) |
| `batches` | Shipment/lot tracking; `qty_purchased`, `qty_received`, `qty_remaining` |
| `inventory_periods` | Accounting periods with costing method + status (open/closed/paused) |
| `inventory_movements` | All stock in/out; `type`: receipt/issue/adjustment/transfer |
| `inventory_consumptions` | COGS breakdown per issue movement (FIFO lots) |

### Purchases
| Table | Purpose |
|---|---|
| `purchases` | 3 types: `local_depot`, `cross_dock`, `import`; 8 statuses |
| `import_nominations` | Transporter + rate setup per import purchase |
| `import_trucks` | Truck-level tracking with full milestone timestamps |
| `batch_costs` | Landed costs (freight, duty, border charges) per batch |

### Sales & Clients
| Table | Purpose |
|---|---|
| `sales` | Sales orders; `client_id` FK + `batch_id` FK for specific_lot costing |
| `clients` | Client master per company |

### Suppliers & Transporters
| Table | Purpose |
|---|---|
| `suppliers` | Supplier master per company |
| `transporters` | Transporter master per company |
| `transporter_ledger_entries` | All financial transactions against a transporter (advances, charges, payments) |

### Petty Cash
| Table | Purpose |
|---|---|
| `petty_cash_accounts` | Float accounts per company |
| `petty_cash_transactions` | All movements in/out of a petty cash account |

### Bulk Import Framework
| Table | Purpose |
|---|---|
| `import_jobs` | Staging table for any bulk upload (Excel/CSV) |
| `import_job_rows` | Per-row validation state with jsonb raw/mapped data |

### Accounting (schema-only — inert until `companies.accounting_enabled = true`)
| Table | Purpose |
|---|---|
| `chart_of_accounts` | G/L account hierarchy (asset/liability/equity/revenue/expense) |
| `journals` | Journal definitions (general/purchase/sale/cash/bank) |
| `journal_entries` | Double-entry journal entries; ties to inventory_periods |
| `journal_entry_lines` | Debit/credit lines per entry |
| `bank_accounts` | Bank accounts with optional G/L link |

### RBAC
| Table | Purpose |
|---|---|
| `roles`, `permissions`, `role_permission`, `user_roles` | Role-based access control |

---

## Full Route Map (55 routes)

```
GET    /                            → redirect/home
GET    /login                       → AuthController@showLogin
POST   /login                       → AuthController@login
POST   /logout                      → AuthController@logout
GET    /dashboard                   → DashboardController@index

-- Company / Onboarding --
GET    /company/create              → Onboarding\CompanyController@create
POST   /company                     → Onboarding\CompanyController@store
GET    /companies/switcher          → CompanySwitcherController@index
POST   /companies                   → CompanySwitcherController@store
GET    /companies/{company}/switch  → CompanySwitcherController@switch

-- Products --
GET    /products                    → ProductController@index
POST   /products                    → ProductController@store
PATCH  /products/{product}          → ProductController@update
PATCH  /products/{product}/toggle-active

-- Purchases (9 routes) --
GET    /purchases                   → PurchaseController@index
POST   /purchases                   → PurchaseController@store
GET    /purchases/create            → PurchaseController@create
GET    /purchases/{purchase}        → PurchaseController@show
POST   /purchases/{purchase}/confirm
POST   /purchases/{purchase}/receive
POST   /purchases/{purchase}/undo-receipt       ← NEW
POST   /purchases/{purchase}/cross-dock-transfer ← NEW
POST   /purchases/{purchase}/cross-dock-dispatch ← NEW

-- Sales --
GET    /sales                       → SalesController@index
POST   /sales                       → SalesController@store
PUT    /sales/{sale}                → SalesController@update
POST   /sales/{sale}/post           → SalesController@post

-- Depot Stock --
GET    /depot-stock                 → DepotStock\DepotStockController@index

-- Settings --
GET/PATCH  /settings/company
GET/POST   /settings/depots
PATCH      /settings/depots/{depot}
PATCH      /settings/depots/{depot}/toggle-active
GET/POST   /settings/suppliers
PATCH      /settings/suppliers/{supplier}
PATCH      /settings/suppliers/{supplier}/toggle-active
GET/POST   /settings/transporters
PATCH      /settings/transporters/{transporter}
PATCH      /settings/transporters/{transporter}/toggle-active
GET        /settings/inventory      → Settings\InventorySettingsController@index
PATCH      /settings/inventory/costing

-- Admin --
GET/POST   /admin/roles
PUT/DELETE /admin/roles/{role}
POST       /admin/roles/{role}/permissions
GET/POST   /admin/users
PATCH      /admin/users/{user}
DELETE/POST /admin/users/{user}/...
```

---

## Key Source Files

| File | Purpose |
|---|---|
| `app/Services/InventoryLedger.php` | Core inventory engine — receipt, issue, weighted avg, FIFO |
| `app/Services/PostingGate.php` | Blocks postings if period closed/paused |
| `app/Services/PeriodResolver.php` | Resolves/auto-creates open inventory period |
| `app/Models/InventoryPeriod.php` | Period model |
| `app/Http/Controllers/PurchaseController.php` | All purchase actions (10 methods) |
| `resources/views/purchases/show.blade.php` | Purchase detail + all action modals (748 lines) |
| `resources/views/purchases/index.blade.php` | Purchase list + filter bar |
| `resources/views/settings/inventory.blade.php` | Inventory settings (costing, periods) |
| `resources/views/layouts/app.blade.php` | Master layout with sidebar nav |
| `.env` | `ASSET_URL=/`, DB creds, `APP_URL` |
| `config/app.php` | Has custom `asset_url` key — do not remove |

---

## What's Next (Roadmap)

### Phase 2 remaining — Import Purchase Pipeline
The `import` type currently has no operational pipeline. Needs:
1. **Nomination** — associate vessel/shipment with a confirmed import batch
2. **Offload tracking** — record discharge qty at port
3. **Deliver to depot** — move offloaded stock into one or more depots (receipt movements)
4. **Logistics costs** — freight, port fees, customs attached to the batch

### Phase 3 — Transporter Ledgers
- Trips logged against transporters
- Freight costs posted to transporter ledger
- Transporter balance / payable tracking

### Phase 4 — Supplier Ledger
- Purchase invoices posted against suppliers
- Supplier balance / payable aging

### Phase 5 — Client AR
- Sales invoices against clients
- AR aging / collections

### Phase 6 — Petty Cash
- Petty cash float per company
- Cash advance / replenishment / reconciliation

### Phase 7 — Banking & Reconciliation
- Bank accounts per company
- Bank statement import / manual entry
- Match payments to supplier/client ledger entries

### Phase 8 — Reporting
- Stock position report (by depot, by product, by period)
- P&L / margin report
- Aging reports (AR, AP)
- Transaction ledger exports

### Phase 9 — Dashboard
- Live KPIs: stock position, open purchases, outstanding AR/AP
- Charts: fuel throughput, margin trends

---

## Environment & Dev Notes

```bash
# Run app
php artisan serve --host=0.0.0.0 --port=5000

# Run migrations
php artisan migrate

# Clear compiled views (after Blade changes)
php artisan view:clear

# Rebuild frontend assets
chmod +x node_modules/.bin/vite
node_modules/.bin/vite build

# List all routes
php artisan route:list
```

- **GitHub remote**: `https://github.com/cabdixakim/twins-erp`
- **Session driver**: file (`storage/framework/sessions/`) — gitignored
- **`.local/` directory**: gitignored (Replit internal)
- **`public/build/`**: gitignored (compiled Vite output)
