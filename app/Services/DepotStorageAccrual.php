<?php

namespace App\Services;

use App\Models\Depot;
use App\Models\DepotChargeConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DepotStorageAccrual
{
    /**
     * Post monthly storage charges for a single depot.
     *
     * Only processes configs with category = 'storage' and rate_unit = 'per_m3_per_month'.
     * Charges are based on qty_on_hand at the END of the target month (closing balance).
     *
     * Idempotent: (batch_id, depot_charge_config_id, charge_period) is unique.
     *
     * Returns array of human-readable summary strings for flash/output.
     */
    public static function postForDepot(
        Depot $depot,
        int   $year,
        int   $month,
        int   $cid,
        int   $createdBy
    ): array {
        $period     = sprintf('%04d-%02d', $year, $month);
        $periodEnd  = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $periodDate = $periodEnd->toDateString();

        // Active storage configs for this depot, effective on the last day of the period
        $configs = DepotChargeConfig::where('company_id', $cid)
            ->where('depot_id', $depot->id)
            ->where('is_active', true)
            ->where('category', 'storage')
            ->where('rate_unit', 'per_m3_per_month')
            ->where('effective_from', '<=', $periodDate)
            ->where(function ($q) use ($periodDate) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $periodDate);
            })
            ->get();

        if ($configs->isEmpty()) {
            return [];
        }

        // All stock currently held at this depot (closing balance)
        $stocks = DB::table('depot_stocks')
            ->where('company_id', $cid)
            ->where('depot_id', $depot->id)
            ->where('qty_on_hand', '>', 0)
            ->get();

        if ($stocks->isEmpty()) {
            return [];
        }

        $posted = [];

        foreach ($stocks as $stock) {
            // Find the earliest receipt date of this batch to this depot
            $receiptAt = DB::table('inventory_movements')
                ->where('company_id', $cid)
                ->where('to_depot_id', $depot->id)
                ->where('batch_id', $stock->batch_id)
                ->where('type', 'receipt')
                ->orderBy('created_at')
                ->value('created_at');

            if (!$receiptAt) {
                continue;  // No receipt movement found — skip
            }

            $receiptDate = Carbon::parse($receiptAt);

            // Get purchase_id for this batch (for batch_costs FK)
            $purchaseId = DB::table('purchases')
                ->where('batch_id', $stock->batch_id)
                ->value('id');

            foreach ($configs as $config) {
                // Check if billing rule allows charging this month
                if (!self::isChargeableForMonth($config, $receiptDate, $year, $month)) {
                    continue;
                }

                // Idempotency: one monthly entry per (batch, config, period)
                $exists = DB::table('batch_costs')
                    ->where('batch_id', $stock->batch_id)
                    ->where('depot_charge_config_id', $config->id)
                    ->where('charge_period', $period)
                    ->exists();
                if ($exists) {
                    continue;
                }

                $qtyM3   = round((float) $stock->qty_on_hand / 1000, 6);
                $amount  = round((float) $config->rate * $qtyM3, 2);

                if ($amount <= 0) {
                    continue;
                }

                // Get product name for the description
                $productName = DB::table('products')
                    ->where('id', $stock->product_id)
                    ->value('name') ?? "Product #{$stock->product_id}";

                $description = "{$config->name} — {$depot->name} — {$period} "
                    . "({$productName}, " . number_format($qtyM3, 3) . " m³ closing balance)";

                DB::table('batch_costs')->insert([
                    'company_id'             => $cid,
                    'batch_id'               => $stock->batch_id,
                    'purchase_id'            => $purchaseId,
                    'depot_charge_config_id' => $config->id,
                    'charge_period'          => $period,
                    'category'               => 'storage',
                    'description'            => $description,
                    'amount'                 => $amount,
                    'currency'               => $config->currency,
                    'exchange_rate'          => 1,
                    'amount_base'            => $amount,
                    'entry_date'             => $periodDate,
                    'is_included_in_cost'    => true,
                    'auto_posted'            => true,
                    'paid_by_type'           => $config->paid_by_type,
                    'paid_by_id'             => $config->paid_by_id,
                    'paid_by_name'           => $config->paid_by_name,
                    'created_by'             => $createdBy,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);

                // Secondary AP: depot ledger entry if paid_by = 'depot'
                if ($config->paid_by_type === 'depot' && $config->paid_by_id) {
                    $alreadyPosted = DB::table('depot_ledger_entries')
                        ->where('depot_id', $config->paid_by_id)
                        ->where('company_id', $cid)
                        ->where('ref_type', 'storage_accrual')
                        ->where('ref_id', $stock->batch_id)
                        ->where('description', 'like', "%{$period}%")
                        ->exists();

                    if (!$alreadyPosted) {
                        DB::table('depot_ledger_entries')->insert([
                            'company_id'  => $cid,
                            'depot_id'    => $config->paid_by_id,
                            'type'        => 'storage_charge',
                            'amount'      => $amount,
                            'currency'    => $config->currency,
                            'description' => $description,
                            'entry_date'  => $periodDate,
                            'ref_type'    => 'storage_accrual',
                            'ref_id'      => $stock->batch_id,
                            'created_by'  => $createdBy,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    }
                }

                $posted[] = "{$productName}: {$config->currency} "
                    . number_format($amount, 2)
                    . " ({$config->name}, " . number_format($qtyM3, 3) . " m³)";
            }
        }

        return $posted;
    }

    /**
     * Dry-run preview — same logic but no DB writes.
     * Returns rows for display in the preview table.
     */
    public static function preview(
        Depot $depot,
        int   $year,
        int   $month,
        int   $cid
    ): array {
        $period     = sprintf('%04d-%02d', $year, $month);
        $periodDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $configs = DepotChargeConfig::where('company_id', $cid)
            ->where('depot_id', $depot->id)
            ->where('is_active', true)
            ->where('category', 'storage')
            ->where('rate_unit', 'per_m3_per_month')
            ->where('effective_from', '<=', $periodDate)
            ->where(function ($q) use ($periodDate) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $periodDate);
            })
            ->get();

        if ($configs->isEmpty()) {
            return ['rows' => [], 'total' => [], 'has_configs' => false];
        }

        $stocks = DB::table('depot_stocks')
            ->where('company_id', $cid)
            ->where('depot_id', $depot->id)
            ->where('qty_on_hand', '>', 0)
            ->get();

        $rows     = [];
        $totals   = [];
        $anyNew   = false;

        foreach ($stocks as $stock) {
            $receiptAt = DB::table('inventory_movements')
                ->where('company_id', $cid)
                ->where('to_depot_id', $depot->id)
                ->where('batch_id', $stock->batch_id)
                ->where('type', 'receipt')
                ->orderBy('created_at')
                ->value('created_at');

            if (!$receiptAt) {
                continue;
            }

            $receiptDate = Carbon::parse($receiptAt);
            $productName = DB::table('products')
                ->where('id', $stock->product_id)
                ->value('name') ?? "Product #{$stock->product_id}";

            foreach ($configs as $config) {
                $chargeable = self::isChargeableForMonth($config, $receiptDate, $year, $month);
                $alreadyPosted = DB::table('batch_costs')
                    ->where('batch_id', $stock->batch_id)
                    ->where('depot_charge_config_id', $config->id)
                    ->where('charge_period', $period)
                    ->exists();

                $qtyM3  = round((float) $stock->qty_on_hand / 1000, 6);
                $amount = $chargeable ? round((float) $config->rate * $qtyM3, 2) : 0;

                if ($chargeable && !$alreadyPosted) {
                    $anyNew = true;
                }

                $rows[] = [
                    'product'        => $productName,
                    'batch_id'       => $stock->batch_id,
                    'qty_m3'         => $qtyM3,
                    'config_name'    => $config->name,
                    'rate'           => (float) $config->rate,
                    'currency'       => $config->currency,
                    'amount'         => $amount,
                    'chargeable'     => $chargeable,
                    'already_posted' => $alreadyPosted,
                    'receipt_date'   => $receiptDate->format('d M Y'),
                    'rule'           => $config->receipt_rule ?? 'include_receipt_month',
                ];

                if ($chargeable && !$alreadyPosted && $amount > 0) {
                    $totals[$config->currency] = ($totals[$config->currency] ?? 0) + $amount;
                }
            }
        }

        return [
            'rows'        => $rows,
            'totals'      => $totals,
            'has_configs' => true,
            'any_new'     => $anyNew,
        ];
    }

    // ── Whether the billing rule allows charging for a given month ───────────

    public static function isChargeableForMonth(
        DepotChargeConfig $config,
        Carbon $receiptDate,
        int    $targetYear,
        int    $targetMonth
    ): bool {
        $rule = $config->receipt_rule ?? 'include_receipt_month';

        // At-delivery already covered the receipt month for include/prorate rules.
        // Monthly accrual ALWAYS starts from the month AFTER receipt for those rules.
        // For exclude rules, same: first monthly accrual is the month after receipt.
        // For exclude_first_30_days: starts from the month that contains receipt + 30 days.
        $startDate = match ($rule) {
            'exclude_first_30_days' => $receiptDate->copy()->addDays(30)->startOfMonth(),
            default                 => $receiptDate->copy()->addMonthNoOverflow()->startOfMonth(),
        };

        $target = Carbon::createFromDate($targetYear, $targetMonth, 1);
        return $target->gte($startDate);
    }
}
