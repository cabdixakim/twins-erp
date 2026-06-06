---
name: Ledger auto-posting pattern
description: When and how purchase invoices are posted to the supplier ledger, and the idempotency contract for all ledger entries.
---

## Rule
Supplier invoice timing follows the moment goods ownership transfers:
- `local_depot` purchase → invoice posted inside `receive()` transaction, after status → 'received'
- `cross_dock` purchase → invoice posted inside `confirm()` transaction (goods enter CROSS DOCK), after status → 'confirmed'; only if `$purchase->type === 'cross_dock'`
- `import` purchase → invoice posted per truck inside `recordDelivery()`, amount = qty_delivered × purchase.unit_price

## Idempotency contract
All ledger posting helpers check for an existing entry with the same (ref_type, ref_id, type) before creating. Never double-post on retry.
`SupplierLedgerController::postInvoice()` is the canonical static helper — call it, don't inline the insert.

**Why:** The DB::transaction wrapping confirm/receive can retry on deadlock; double-posting would corrupt supplier balances.

## How to apply
Any new purchase action that represents goods receipt or financial commitment must call `SupplierLedgerController::postInvoice()` inside the same DB::transaction. Use the purchase's `ref_type='purchase', ref_id=$purchase->id` as the idempotency key for one-shot events, or `ref_type=ImportTruck::class, ref_id=$truck->id` for per-truck events.
