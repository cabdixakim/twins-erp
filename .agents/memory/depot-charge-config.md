---
name: Depot charge config system
description: Per-depot landed cost automation — how charges are configured, calculated, and posted at truck delivery
---

# Depot charge config system

## Tables involved
- `depot_charge_configs` — rate cards per depot (one row per charge type + effective period)
- `batch_costs.depot_charge_config_id` — traceability FK (nullable)
- `import_nominations.destination_depot_id` — default delivery depot (pre-fills modals)

## Calculation priority at delivery
1. Truck-level override (Phase B — not yet built; will be `truck_charge_overrides` table)
2. `import_nominations.hospitality_rate` — overrides rate for **storage category only**
3. `depot_charge_configs` active record for the truck's delivery depot on that date

## Receipt billing rules (storage only)
- `include_receipt_month` → post full month at delivery
- `prorate_receipt_month` → days remaining in month ÷ days in month × rate × m³
- `exclude_receipt_month` → $0 at delivery; real charge deferred (monthly job needed)
- `exclude_first_30_days` → $0 at delivery; charge starts day 31 (monthly job needed)

**Why:** Industry standard is to charge on closing balance at month-end, but at delivery
we can only charge for the partial month. Deferred rules post $0 as placeholder so the
monthly job can see when storage started.

## Paid-by routing
- `self` → batch cost only (no secondary AP)
- `depot` + paid_by_id → batch cost + `depot_ledger_entries` (type mapped by category)
- `transporter` + paid_by_id → batch cost + `transporter_ledger_entries` (type = advance)
- `customs_authority` / `other` → batch cost only (customs ledger is v2)

## Idempotency
Both `postForDelivery()` and secondary AP inserts check `truck_id + depot_charge_config_id`
before inserting. Safe to retry delivery recording.

## Rate unit conversions
Stock is tracked in Litres (L). Storage/offloading rates are in m³.
Conversion: `qty_m3 = qty_litres / 1000`

## What's NOT yet built (v2)
- Monthly storage accrual job (for deferred billing rules)
- Per-truck charge overrides / exemptions
- Customs authority ledger (currently batch cost only)
- Dispatch month billing rule enforcement (schema has dispatch_rule column, not yet used)
