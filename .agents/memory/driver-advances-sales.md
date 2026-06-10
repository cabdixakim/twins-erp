---
name: Driver advances on sales
description: How trip/fuel advances work when creating a delivered sale
---

## Rule
When a delivered sale is created/edited with a petty cash account + advance amounts,
`SalesController::postDriverAdvances()` creates `PettyCashTransaction` rows
(type=expense, category=trip_advance or fuel_advance, ref_type=sale, ref_id=sale.id).

On update, the old advance transactions are deleted first (by ref_type=sale, ref_id, category),
then postDriverAdvances() is called fresh — simple idempotency.

## Columns added to `sales` table
- `trip_advance` decimal(15,4) nullable
- `fuel_advance`  decimal(15,4) nullable
- `advance_currency` varchar(8) nullable
- `advance_account_id` bigint nullable (FK to petty_cash_accounts)

**Why:** User wants to give driver cash (trip money + fuel money) at the moment they dispatch
a truck, not as a separate petty cash entry. Linking via ref_type='sale' means the petty cash
ledger shows which sale each advance belongs to.

**How to apply:** The Driver Advances section only shows in the modal when delivery_mode=delivered.
No advance is posted if advance_account_id is blank or both amounts are 0.
