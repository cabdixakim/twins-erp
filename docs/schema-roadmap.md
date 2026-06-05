# Twins ERP — Complete Database Schema Roadmap

> **Authoritative reference** — all future feature work must follow this document.
> Last updated: 2026-06-05

---

## Purpose

This document defines the complete database schema for Twins ERP, including:
- Every table that exists today
- Every gap that has been fixed
- Every table being created for planned future modules

It prevents the most common ERP technical debt: migrations that alter live tables, or schema decisions that close off future options.

---

## Schema Gap Fixes Applied

The following corrections were applied via migrations `2026_06_05_000001` through `2026_06_05_000004`.

### 1. `products.allowed_loss_pct` ✅
- **Added**: `allowed_loss_pct` decimal(8,4) nullable
- **Why**: The import shortfall calculation was hardcoding 0.3% for AGO and 0.5% for PMS in `ImportNominationController`. This config belongs on the product.
- **Hierarchy rule**: nomination-level `allowed_loss_pct` overrides product-level if explicitly set; product-level is used as the default when creating a nomination.

### 2. `sales.client_id` and `sales.batch_id` ✅
- **Added**: `client_id` nullable FK → `clients` (after `client_name`)
- **Added**: `batch_id` nullable FK → `batches` (after `client_id`)
- **Why**: `client_name` is a free-text field with no relational link. The `clients` module now exists; sales must be linkable to a client record. `batch_id` is required for `specific_lot` costing so the user can declare which lot stock is being sold from.

### 3. `purchases.type` — no migration needed
- The `type` column is `string(24)` (not a DB enum), so `cross_dock` already works at the DB level. The comment in the original migration was wrong (`import|local_depot`). No schema change is needed; the code already handles all three types.

### 4. `import_trucks` milestone timestamps ✅
- **Added**: `failed_at` timestamp nullable
- **Added**: `failure_reason` string(500) nullable
- **Added**: `in_transit_at` timestamp nullable
- **Added**: `border_cleared_at` timestamp nullable
- **Why**: Previously only status strings were stored, losing the *when* of each transition. Auditability requires timestamps at each milestone.

### 5. `companies.accounting_enabled` + `companies.inventory_periods_enabled` ✅
- **Added**: `accounting_enabled` boolean default false
- **Added**: `inventory_periods_enabled` boolean default false
- **Why**: The accounting tables are created schema-only; they must remain inert until opted in. `inventory_periods_enabled` moves period tracking from always-on to opt-in, supporting companies that want simpler stock-only mode.

### 6. `depots.default_shrinkage_pct` — already exists ✅
- Column exists as `decimal(5,4) default 0.3000`. No action needed.
- **Hierarchy rule**: Product `allowed_loss_pct` takes precedence for import shortfall; depot `default_shrinkage_pct` applies to storage/evaporation calculations (a separate concern).

---

## Complete Table Reference

### Core / Multi-Tenant

#### `companies`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string | |
| slug | string unique | |
| logo_path | string nullable | |
| base_currency | string | default USD |
| country | string nullable | |
| timezone | string | default Africa/Lubumbashi |
| code | string(32) nullable | used in PO reference generation |
| costing_method | string | weighted_average \| specific_lot |
| **weighted_avg_cost** | decimal(15,4) nullable | company-wide weighted avg (cached) |
| **accounting_enabled** | boolean | default false — accounting module gate |
| **inventory_periods_enabled** | boolean | default false — period tracking gate |
| inventory_posting_paused | boolean | |
| posting_paused_at | timestamp nullable | |
| posting_paused_by | bigint nullable | |
| posting_paused_reason | text nullable | |
| timestamps | | |

#### `users`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| password | string | |
| active_company_id | FK → companies nullable | currently active company |
| role | string | |
| status | string | |
| remember_token | string nullable | |
| timestamps | | |

#### `company_user`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| user_id | FK → users | |
| timestamps | | |

---

### Products & Inventory

#### `products`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| name | string(120) | e.g. AGO, PMS, Jet A-1 |
| code | string(32) nullable | |
| category | string(32) nullable | |
| base_uom | string(16) | default L |
| is_active | boolean | |
| **allowed_loss_pct** | decimal(8,4) nullable | product-level default shrinkage % for import |
| created_by / updated_by | FK → users nullable | |
| timestamps | | |

#### `depots`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| name | string | |
| city | string nullable | |
| storage_fee_per_1000_l | decimal(12,4) | |
| default_shrinkage_pct | decimal(5,4) | default 0.3000 — storage/evaporation |
| is_active | boolean | |
| is_system | boolean | true for CROSS DOCK depot |
| notes | text nullable | |
| timestamps | | |

#### `batches`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| product_id | FK → products | |
| code | string(64) nullable | auto-generated e.g. BATCH-2026-0007 |
| name | string(160) nullable | |
| source_type | string(24) | import \| local_depot |
| source_ref | string(120) nullable | supplier invoice/ref |
| supplier_id | FK → suppliers nullable | |
| qty_purchased | decimal(18,3) | |
| qty_received | decimal(18,3) | |
| qty_remaining | decimal(18,3) | |
| total_cost | decimal(18,2) | |
| unit_cost | decimal(18,6) | |
| status | string(24) | draft \| active \| closed \| cancelled |
| purchased_at | timestamp nullable | |
| created_by / updated_by | FK → users nullable | |
| timestamps | | |

#### `depot_stocks`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| depot_id | FK → depots | |
| product_id | FK → products | |
| batch_id | FK → batches nullable | |
| qty_on_hand | decimal(18,3) | running balance |
| qty_reserved | decimal(18,3) | reserved for pending orders |
| unit_cost | decimal(18,6) | weighted avg cost at this row |
| created_by / updated_by | FK → users nullable | |
| timestamps | | |

#### `inventory_periods`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| name | string | |
| starts_at | date | |
| ends_at | date nullable | |
| status | string | open \| closed \| paused |
| costing_method | string | weighted_average \| specific_lot |
| timestamps | | |

#### `inventory_movements`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| period_id | FK → inventory_periods nullable | |
| depot_id | FK → depots | |
| product_id | FK → products | |
| batch_id | FK → batches nullable | |
| type | string | receipt \| issue \| adjustment \| transfer |
| qty | decimal(15,3) | positive = in, negative = out |
| unit_cost | decimal(18,6) | |
| ref_type | string nullable | morphable source |
| ref_id | bigint nullable | |
| notes | text nullable | |
| created_by / updated_by | FK → users nullable | |
| timestamps | | |

#### `inventory_consumptions`
COGS breakdown per issue movement (FIFO lots).

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| period_id | FK → inventory_periods nullable | |
| movement_id | FK → inventory_movements | |
| batch_id | FK → batches | |
| qty | decimal(15,3) | qty consumed from this lot |
| unit_cost | decimal(18,6) | cost of this lot |
| total_cost | decimal(18,4) | qty × unit_cost |
| timestamps | | |

---

### Purchases

#### `purchases`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| type | string(24) | **local_depot \| cross_dock \| import** |
| supplier_id | FK → suppliers nullable | |
| product_id | FK → products | |
| batch_id | FK → batches nullable | created on confirm |
| depot_id | FK → depots nullable | |
| client_id | FK → clients nullable | set on cross_dock dispatch |
| purchase_date | date nullable | |
| qty | decimal(18,3) | |
| unit_price | decimal(18,6) | |
| currency | string(8) | |
| reference | string(64) | auto-generated as PO-CODE-YEAR-SEQ |
| sequence_no | bigint | |
| status | string(24) | draft \| confirmed \| received \| nominated \| transferred \| dispatched \| cancelled \| voided |
| actioned_at | timestamp nullable | |
| actioned_by | bigint nullable | |
| action_note | string(500) nullable | |
| bl_date / eta_date | date nullable | import fields |
| notes | text nullable | |
| created_by / updated_by | FK → users nullable | |
| timestamps | | |

#### `import_nominations`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| purchase_id | FK → purchases | |
| transporter_id | FK → transporters nullable | |
| currency / rate_per_1000l | | freight rate |
| allowed_loss_pct | decimal(8,4) | overrides product default |
| short_charge_rate / short_charge_currency | | |
| advances / advances_currency | | |
| notes | text nullable | |
| status | string(24) | |
| created_by | FK → users nullable | |
| timestamps | | |

#### `import_trucks`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| nomination_id | FK → import_nominations | |
| truck_reg / trailer_reg | string nullable | |
| driver_name / driver_passport / driver_license / driver_phone | string nullable | |
| capacity | decimal(15,3) | expected litres |
| status | string(30) | nominated \| loading_failed \| loaded \| in_transit \| border_cleared \| delivered |
| qty_loaded | decimal(15,3) nullable | |
| pickup_date / pickup_terminal | | |
| load_notes | text nullable | |
| **failed_at** | timestamp nullable | when loading_failed status was set |
| **failure_reason** | string(500) nullable | |
| **in_transit_at** | timestamp nullable | |
| **border_cleared_at** | timestamp nullable | |
| tr8_number / t1_number / border_date | | border clearance |
| depot_id | FK → depots nullable | delivery destination |
| qty_delivered | decimal(15,3) nullable | |
| delivery_date / delivery_notes | | |
| shortfall_qty / allowed_loss_qty / excess_loss_qty / shortfall_charge | decimal nullable | computed at delivery |
| notes | text nullable | |
| created_by | FK → users nullable | |
| timestamps | | |

---

### Sales

#### `sales`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| depot_id | FK → depots | |
| product_id | FK → products | |
| client_name | string nullable | free-text (legacy) |
| **client_id** | FK → clients nullable | relational link to clients module |
| **batch_id** | FK → batches nullable | required for specific_lot costing |
| sequence_no / reference | | auto-generated |
| sale_date | date nullable | |
| qty | decimal(18,3) | |
| unit_price | decimal(18,6) | |
| currency | string(8) | |
| total / cogs_total / gross_profit | decimal | filled on post |
| status | string(16) | draft \| posted \| cancelled |
| delivery_mode | string(16) | ex_depot \| delivered |
| transporter_id | FK → transporters nullable | |
| truck_no / trailer_no / waybill_no | string nullable | |
| delivery_notes | text nullable | |
| inventory_movement_id | FK → inventory_movements nullable | |
| posted_by | FK → users nullable | |
| posted_at | timestamp nullable | |
| created_by / updated_by | FK → users nullable | |
| timestamps | | |

---

### Clients

#### `clients`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| name | string | |
| code | string nullable | |
| type | string nullable | |
| country / city | string nullable | |
| contact_person / phone / email | string nullable | |
| currency | string | |
| credit_limit | decimal nullable | |
| is_active | boolean | |
| notes | text nullable | |
| timestamps | | |

---

### Suppliers & Transporters

#### `suppliers`
Standard supplier master with `company_id`, `name`, `code`, `contact`, `is_active`.

#### `transporters`
Standard transporter master with `company_id`, `name`, `code`, `contact`, `is_active`.

---

### Transporter Ledger (Phase 3)

#### `transporter_ledger_entries`
Single ledger for all financial transactions against a transporter. Running balance = `SUM(amount)` per transporter. Both international and local transporters share this table.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| transporter_id | FK → transporters | |
| type | string(32) | advance \| recovery \| payment \| short_charge \| freight_charge |
| ref_type | string(80) nullable | morphable — ImportNomination, Sale, etc. |
| ref_id | bigint nullable | |
| amount | decimal(15,4) | positive = debit (owed to transporter), negative = credit |
| currency | string(8) | |
| description | string(500) | |
| entry_date | date | |
| created_by | FK → users nullable | |
| timestamps | | |

---

### Petty Cash (Phase 6)

#### `petty_cash_accounts`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| name | string(150) | e.g. "Main Petty Cash" |
| currency | string(8) | |
| opening_balance | decimal(15,4) | |
| is_active | boolean | |
| timestamps | | |

#### `petty_cash_transactions`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| account_id | FK → petty_cash_accounts | |
| type | string(40) | bank_transfer_in \| transporter_advance \| driver_advance \| operational_expense \| recovery \| adjustment |
| ref_type | string(80) nullable | morphable |
| ref_id | bigint nullable | |
| amount | decimal(15,4) | positive = inflow, negative = outflow |
| currency | string(8) | |
| description | string(500) | |
| receipt_path | string(500) nullable | file attachment |
| transaction_date | date | |
| created_by | FK → users nullable | |
| timestamps | | |

---

### Landed Cost / Batch Costs (Phase 2 extension)

#### `batch_costs`
All non-purchase costs allocated to a batch (freight, duties, border charges, etc.).
Used to compute landed cost and optionally roll into weighted average unit cost.

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| batch_id | FK → batches | |
| purchase_id | FK → purchases nullable | |
| nomination_id | FK → import_nominations nullable | |
| category | string(40) | freight \| duty \| border_charge \| hospitality \| storage \| penalty \| other |
| description | string(500) | |
| amount | decimal(15,4) | |
| currency | string(8) | |
| exchange_rate | decimal(12,6) | default 1 |
| amount_base | decimal(15,4) | amount × exchange_rate in base currency |
| is_included_in_cost | boolean | when true, rolled into batch unit_cost |
| entry_date | date | |
| created_by | FK → users nullable | |
| timestamps | | |

---

### Bulk Import Framework

#### `import_jobs`
Staging table for any bulk upload (nominations, offloads, opening balances, etc.).

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| type | string(60) | nominations \| offloads \| purchases \| opening_balances \| payments \| clients |
| ref_type | string(80) nullable | |
| ref_id | bigint nullable | |
| filename | string(500) | original uploaded filename |
| status | string(24) | pending \| validating \| validated \| posting \| posted \| failed |
| row_count / valid_count / error_count | integer | |
| posted_by | FK → users nullable | |
| posted_at | timestamp nullable | |
| created_by | FK → users nullable | |
| timestamps | | |

#### `import_job_rows`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| job_id | FK → import_jobs | |
| row_number | integer | |
| raw_data | jsonb | original row as parsed from Excel/CSV |
| mapped_data | jsonb nullable | after field mapping/normalization |
| status | string(24) | pending \| valid \| invalid \| posted \| skipped |
| errors | jsonb nullable | array of error messages per field |
| result_type | string(80) nullable | model type created on post |
| result_id | bigint nullable | ID of created record |
| timestamps | | |

---

### Accounting Module (Schema-only — Phase 7+)

> **Gate**: these tables are inert until `companies.accounting_enabled = true`.
> All operational reports (stock position, margin, aging) must work **without** these tables.

#### `chart_of_accounts`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| code | string(32) | e.g. 1000, 2100 |
| name | string(200) | |
| type | string(24) | asset \| liability \| equity \| revenue \| expense |
| sub_type | string(40) nullable | current_asset \| fixed_asset \| current_liability \| etc. |
| parent_id | FK → self nullable | account hierarchy |
| is_system | boolean | system accounts cannot be deleted |
| is_active | boolean | |
| timestamps | | |

#### `journals`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| name | string(150) | e.g. "General Journal", "Purchase Journal" |
| type | string(24) | general \| purchase \| sale \| cash \| bank |
| is_active | boolean | |
| timestamps | | |

#### `journal_entries`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| journal_id | FK → journals | |
| period_id | FK → inventory_periods nullable | ties to stock period |
| reference | string(80) | |
| description | string(500) | |
| entry_date | date | |
| status | string(24) | draft \| posted \| reversed |
| ref_type / ref_id | morphable | source document |
| posted_by / posted_at | | |
| reversed_by / reversed_at | | |
| timestamps | | |

#### `journal_entry_lines`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| entry_id | FK → journal_entries | |
| account_id | FK → chart_of_accounts | |
| description | string(500) nullable | |
| debit | decimal(15,4) | default 0 |
| credit | decimal(15,4) | default 0 |
| timestamps | | |

#### `bank_accounts`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| company_id | FK → companies | |
| name | string(150) | |
| account_number | string(80) nullable | |
| bank_name | string(150) nullable | |
| currency | string(8) | |
| opening_balance | decimal(15,4) | |
| gl_account_id | FK → chart_of_accounts nullable | links to G/L |
| is_active | boolean | |
| timestamps | | |

---

## RBAC

| Table | Purpose |
|---|---|
| `roles` | Role definitions per company |
| `permissions` | Permission definitions |
| `role_permission` | M2M roles ↔ permissions (actual table name) |
| `user_roles` | M2M users ↔ roles |

---

## Costing Hierarchy Rules

1. **Weighted average** (default): after every receipt, recalculate `unit_cost = total_value / total_qty` for the (company, product, depot) combination. Propagate to all matching `depot_stock` rows.
2. **Specific lot**: user must select `batch_id` on each sale. FIFO is used when a sale spans multiple lots.
3. **Landed cost**: `batch_costs` with `is_included_in_cost = true` are summed and divided by `batch.qty_received` to get additional unit cost. This supplement is added to the base `unit_cost` before posting issue movements.

## Shrinkage Hierarchy Rules

1. `import_nominations.allowed_loss_pct` — set per nomination; overrides all others for import truck shortfall calculation.
2. `products.allowed_loss_pct` — used as the default when creating a new nomination if not overridden.
3. `depots.default_shrinkage_pct` — applies to storage/evaporation calculations (a separate concern from import shortfall).

---

## Migration Inventory

| Migration File | Purpose |
|---|---|
| 2025_01_01_000000 | companies + users + company_user |
| 2025_11_20_000001 | roles |
| 2025_11_20_140241 | cache |
| 2025_11_21_000001 | role_permissions |
| 2025_11_22_000000 | role + status on users |
| 2025_12_04_000000 | depots |
| 2025_12_04_000001 | transporters |
| 2025_12_04_000002 | suppliers |
| 2026_02_01_000000 | products |
| 2026_02_04_000000 | batches |
| 2026_02_04_000001 | depot_stock |
| 2026_02_04_000002 | inventory_movements |
| 2026_02_04_000003 | inventory_consumptions |
| 2026_02_05_000000 | purchases |
| 2026_02_06_192441 | depot_id on purchases |
| 2026_02_06_192508 | is_system on depots |
| 2026_02_09_000000 | reference on purchases |
| 2026_02_09_000001 | code on companies |
| 2026_02_13_000001 | updated_by on inventory_movements |
| 2026_02_14_000001 | sales |
| 2026_02_14_000002 | updated_by on movements + consumptions |
| 2026_04_03_000001 | costing fields on companies |
| 2026_04_03_000002 | inventory_periods |
| 2026_04_03_000003 | period_id on inventory_movements |
| 2026_04_03_000004 | period_id on inventory_consumptions |
| 2026_04_03_000005 | actioned_at/by/note on purchases |
| 2026_04_03_223654 | nomination fields on purchases |
| 2026_04_04_000001 | clients |
| 2026_04_04_000002 | client_id on purchases |
| 2026_04_04_100001 | import_nominations |
| 2026_04_04_100002 | import_trucks |
| **2026_06_05_000001** | **products.allowed_loss_pct (gap fix)** |
| **2026_06_05_000002** | **sales.client_id + sales.batch_id (gap fix)** |
| **2026_06_05_000003** | **import_trucks milestone timestamps (gap fix)** |
| **2026_06_05_000004** | **companies.accounting_enabled + inventory_periods_enabled (gap fix)** |
| **2026_06_05_000005** | **transporter_ledger_entries (new)** |
| **2026_06_05_000006** | **petty_cash_accounts + petty_cash_transactions (new)** |
| **2026_06_05_000007** | **batch_costs (new)** |
| **2026_06_05_000008** | **import_jobs + import_job_rows (new)** |
| **2026_06_05_000009** | **chart_of_accounts + journals + journal_entries + journal_entry_lines + bank_accounts (new)** |
| **2026_06_05_000010** | **companies.weighted_avg_cost decimal(15,4) nullable (gap fix)** |
