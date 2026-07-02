---
name: Sale vs internal-transfer movement tagging
description: How to distinguish real sales from internal stock moves in inventory_movements when computing sales/dispatch totals.
---

`inventory_movements` rows with `type = 'issue'` are NOT all sales — some are internal stock relocations. When aggregating "sales" for reports/ledgers:

- `ref_type = 'sale'` → real sale posted through the Sales module.
- `reference LIKE 'cross-dock-dispatch:%'` → cross-dock stock dispatched directly to a client — this IS a real sale, just posted from the Purchases/cross-dock flow instead of the Sales module.
- `reference LIKE 'cross-dock-transfer:%'` → internal transfer from the virtual CROSS DOCK depot into a real depot — NOT a sale, ownership doesn't change, must be excluded from sales/dispatch totals.

**Why:** cross-dock purchases can either be transferred to an owned depot (internal move, no revenue) or dispatched straight to a client (revenue event). Both post as `issue` movements out of the CROSS DOCK depot, so `reference` prefix is the only reliable way to tell them apart — `ref_type` alone is not set consistently for the dispatch case.

**How to apply:** any report/ledger that sums "sales" or "dispatches" from `inventory_movements` should filter `ref_type = 'sale' OR reference LIKE 'cross-dock-dispatch:%'`, and explicitly exclude `cross-dock-transfer:%`.
