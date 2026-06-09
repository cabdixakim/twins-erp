<?php

namespace App\Services;

use App\Models\DepotChargeConfig;
use App\Models\ImportNomination;
use App\Models\ImportTruck;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DepotChargeAutoPost
{
    /**
     * Auto-post all applicable depot charge configs for a single truck delivery.
     *
     * Priority:
     *  1. If nomination->hospitality_rate > 0, it overrides the rate for 'storage' configs.
     *  2. Otherwise the depot_charge_config rate is used.
     *
     * Returns an array of human-readable cost summary strings (for flash messages).
     */
    public static function postForDelivery(
        ImportTruck      $truck,
        int              $depotId,
        float            $qtyDeliveredL,
        string           $deliveryDate,
        Purchase         $purchase,
        ImportNomination $nomination,
        int              $cid,
        int              $createdBy
    ): array {
        if (!$purchase->batch_id) {
            return [];
        }

        $qtyM3   = $qtyDeliveredL / 1000;   // Litres → m³
        $configs = DepotChargeConfig::activeForDepot($depotId, $cid, $deliveryDate);
        $posted  = [];

        foreach ($configs as $config) {
            // Exempt = contractually waived, post nothing
            if ($config->paid_by_type === 'exempt') {
                continue;
            }

            // Idempotency: one auto-posted entry per (truck, config)
            $exists = DB::table('batch_costs')
                ->where('truck_id', $truck->id)
                ->where('depot_charge_config_id', $config->id)
                ->exists();
            if ($exists) {
                continue;
            }

            // Resolve rate (nomination-level storage override takes priority)
            if ($config->category === 'storage' && (float) $nomination->hospitality_rate > 0) {
                $rate     = (float) $nomination->hospitality_rate;
                $currency = $nomination->hospitality_currency ?? $config->currency;
            } else {
                $rate     = (float) $config->rate;
                $currency = $config->currency;
            }

            // Calculate amount
            $amount = self::calculateAmount($config, $rate, $qtyM3, $deliveryDate);

            // For "start from next month" rules, amount is 0 at delivery — still record
            // a $0 batch cost so the monthly job knows when storage started.
            $description = self::buildDescription($config, $truck, $qtyDeliveredL, $qtyM3, $deliveryDate);

            DB::table('batch_costs')->insert([
                'company_id'             => $cid,
                'batch_id'               => $purchase->batch_id,
                'purchase_id'            => $purchase->id,
                'nomination_id'          => $nomination->id,
                'truck_id'               => $truck->id,
                'depot_charge_config_id' => $config->id,
                'category'               => $config->category,
                'description'            => $description,
                'amount'                 => $amount,
                'currency'               => $currency,
                'exchange_rate'          => 1,
                'amount_base'            => $amount,
                'entry_date'             => $deliveryDate,
                'is_included_in_cost'    => true,
                'auto_posted'            => true,
                'paid_by_type'           => $config->paid_by_type,
                'paid_by_id'             => $config->paid_by_id,
                'paid_by_name'           => $config->paid_by_name,
                'created_by'             => $createdBy,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);

            // Secondary AP entry (depot ledger, customs, etc.)
            if ($amount > 0) {
                self::postSecondaryAP($config, $amount, $currency, $purchase, $truck, $deliveryDate, $cid, $createdBy);
            }

            if ($amount > 0) {
                $posted[] = "{$config->name}: {$currency} " . number_format($amount, 2);
            } else {
                $posted[] = "{$config->name}: deferred (billing starts " . self::deferredStartLabel($config, $deliveryDate) . ")";
            }
        }

        return $posted;
    }

    // ── Amount calculation ───────────────────────────────────────────────────

    private static function calculateAmount(
        DepotChargeConfig $config,
        float $rate,
        float $qtyM3,
        string $deliveryDate
    ): float {
        return match ($config->rate_unit) {
            'per_m3_per_month' => self::calcStorageAmount($config, $rate, $qtyM3, $deliveryDate),
            'per_m3'           => round($rate * $qtyM3, 2),
            'per_trip'         => round($rate, 2),
            'lump_sum'         => round($rate, 2),
            default            => round($rate * $qtyM3, 2),
        };
    }

    private static function calcStorageAmount(
        DepotChargeConfig $config,
        float $rate,
        float $qtyM3,
        string $deliveryDate
    ): float {
        $rule = $config->receipt_rule ?? 'include_receipt_month';

        return match ($rule) {
            'include_receipt_month'  => round($rate * $qtyM3, 2),
            'exclude_receipt_month'  => 0.0,   // charge deferred; monthly job handles it
            'prorate_receipt_month'  => self::prorateDaysRemaining($rate, $qtyM3, $deliveryDate),
            'exclude_first_30_days'  => 0.0,   // charge deferred 30 days out
            default                  => round($rate * $qtyM3, 2),
        };
    }

    private static function prorateDaysRemaining(float $rate, float $qtyM3, string $deliveryDate): float
    {
        $dt           = Carbon::parse($deliveryDate);
        $daysInMonth  = $dt->daysInMonth;
        $dayOfMonth   = $dt->day;
        $daysRemaining = $daysInMonth - $dayOfMonth + 1;  // inclusive of delivery day

        return round($rate * $qtyM3 * $daysRemaining / $daysInMonth, 2);
    }

    // ── Secondary AP entry ───────────────────────────────────────────────────

    private static function postSecondaryAP(
        DepotChargeConfig $config,
        float $amount,
        string $currency,
        Purchase $purchase,
        ImportTruck $truck,
        string $deliveryDate,
        int $cid,
        int $createdBy
    ): void {
        $paidByType = $config->paid_by_type;

        if ($paidByType === 'depot' && $config->paid_by_id) {
            // Map our batch cost category to the depot ledger entry type
            $ledgerType = match ($config->category) {
                'storage'    => 'storage_charge',
                'offloading' => 'loading_fee',
                'duty'       => 'other_charge',
                'customs'    => 'other_charge',
                default      => 'other_charge',
            };

            // Idempotency: one ledger entry per (truck, config)
            $exists = DB::table('depot_ledger_entries')
                ->where('ref_type', ImportTruck::class)
                ->where('ref_id', $truck->id)
                ->where('type', $ledgerType)
                ->where('depot_id', $config->paid_by_id)
                ->where(DB::raw("description"), 'like', "%{$config->name}%")
                ->exists();

            if (!$exists) {
                DB::table('depot_ledger_entries')->insert([
                    'company_id'  => $cid,
                    'depot_id'    => $config->paid_by_id,
                    'type'        => $ledgerType,
                    'amount'      => $amount,
                    'currency'    => $currency,
                    'description' => "{$config->name} — {$truck->truck_reg} (PO {$purchase->reference})",
                    'entry_date'  => $deliveryDate,
                    'ref_type'    => ImportTruck::class,
                    'ref_id'      => $truck->id,
                    'created_by'  => $createdBy,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        } elseif ($paidByType === 'transporter' && $config->paid_by_id) {
            // Clearing agent / transporter fronted the cost
            $exists = DB::table('transporter_ledger_entries')
                ->where('ref_type', ImportTruck::class)
                ->where('ref_id', $truck->id)
                ->where('transporter_id', $config->paid_by_id)
                ->where(DB::raw("description"), 'like', "%{$config->name}%")
                ->exists();

            if (!$exists) {
                DB::table('transporter_ledger_entries')->insert([
                    'company_id'     => $cid,
                    'transporter_id' => $config->paid_by_id,
                    'type'           => 'advance',
                    'amount'         => $amount,
                    'currency'       => $currency,
                    'description'    => "{$config->name} fronted by transporter — {$truck->truck_reg}",
                    'entry_date'     => $deliveryDate,
                    'ref_type'       => ImportTruck::class,
                    'ref_id'         => $truck->id,
                    'created_by'     => $createdBy,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
        // 'self' | 'customs_authority' | 'other' | null → batch cost only, no secondary AP
    }

    // ── Description helpers ──────────────────────────────────────────────────

    private static function buildDescription(
        DepotChargeConfig $config,
        ImportTruck $truck,
        float $qtyL,
        float $qtyM3,
        string $deliveryDate
    ): string {
        $qty = match ($config->rate_unit) {
            'per_m3_per_month', 'per_m3' => number_format($qtyM3, 3) . ' m³',
            default                       => '',
        };

        $base = "{$config->name} — {$truck->truck_reg}";

        if ($qty) {
            $base .= " ({$qty})";
        }

        if ($config->category === 'storage') {
            $rule  = $config->receipt_rule ?? 'include_receipt_month';
            $month = Carbon::parse($deliveryDate)->format('M Y');
            $base  .= " · {$month}";

            if (in_array($rule, ['exclude_receipt_month', 'exclude_first_30_days'], true)) {
                $startLabel = self::deferredStartLabel($config, $deliveryDate);
                $base       .= " · starts {$startLabel}";
            }
        }

        return $base;
    }

    private static function deferredStartLabel(DepotChargeConfig $config, string $deliveryDate): string
    {
        $dt = Carbon::parse($deliveryDate);

        return match ($config->receipt_rule) {
            'exclude_receipt_month' => $dt->copy()->addMonthNoOverflow()->startOfMonth()->format('d M Y'),
            'exclude_first_30_days' => $dt->copy()->addDays(30)->format('d M Y'),
            default                 => '?',
        };
    }

    // ── Preview (no DB writes) ───────────────────────────────────────────────
    // Used in delivery modal to show "charges that will be posted"

    public static function preview(
        int              $depotId,
        float            $qtyDeliveredL,
        string           $deliveryDate,
        ImportNomination $nomination,
        int              $cid
    ): array {
        $qtyM3   = $qtyDeliveredL / 1000;
        $configs = DepotChargeConfig::activeForDepot($depotId, $cid, $deliveryDate);
        $rows    = [];

        foreach ($configs as $config) {
            if ($config->category === 'storage' && (float) $nomination->hospitality_rate > 0) {
                $rate     = (float) $nomination->hospitality_rate;
                $currency = $nomination->hospitality_currency ?? $config->currency;
            } else {
                $rate     = (float) $config->rate;
                $currency = $config->currency;
            }

            $amount  = self::calculateAmount($config, $rate, $qtyM3, $deliveryDate);
            $rows[]  = [
                'name'     => $config->name,
                'category' => $config->category,
                'amount'   => $amount,
                'currency' => $currency,
                'deferred' => $amount <= 0,
                'rule'     => $config->receipt_rule,
            ];
        }

        return $rows;
    }
}
