<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\ImportTruck;
use App\Models\Purchase;
use App\Models\DutyLedgerEntry;
use App\Models\SupplierLedgerEntry;
use App\Models\DepotLedgerEntry;

class DutyPostingService
{
    /**
     * Auto-post duty for a truck at border clearance or at any point.
     * Idempotent — will not double-post if duty_status is already 'posted'.
     */
    public static function postForTruck(ImportTruck $truck, int $userId): ?string
    {
        if ($truck->duty_status === 'posted') {
            return null;
        }
        if ($truck->duty_status === 'waived') {
            return null;
        }

        $vendorType = $truck->duty_vendor_type;
        $vendorId   = (int) ($truck->duty_vendor_id ?? 0);
        $amount     = (float) ($truck->duty_amount ?? 0);
        $currency   = $truck->duty_currency ?: 'USD';
        $rate       = (float) ($truck->duty_rate_per_1000l ?? 0);
        $qty        = (float) ($truck->duty_qty ?? $truck->qty_loaded ?? 0);

        if ($amount <= 0 && $rate > 0 && $qty > 0) {
            $amount = round($rate * $qty / 1000, 4);
        }

        if ($amount <= 0 || !$vendorType || $vendorType === 'self') {
            // No AP entry — just mark posted if duty amount is known
            if ($amount > 0) {
                self::createBatchCostEntry($truck, $amount, $currency, $userId);
            }
            $truck->update(['duty_status' => 'posted', 'duty_posted_at' => now()]);
            return null;
        }

        $nomination = $truck->nomination;
        $purchase   = $nomination?->purchase;
        $cid        = (int) $truck->company_id;
        $date       = $truck->border_date?->format('Y-m-d') ?? now()->toDateString();
        $desc       = "Duty — truck {$truck->truck_reg}" . ($purchase ? " — PO {$purchase->reference}" : '');

        // Strict vendor validation: AP type requires a non-zero, company-owned vendor ID
        $apTypes = ['customs_authority', 'supplier', 'depot', 'transporter'];
        if (in_array($vendorType, $apTypes, true)) {
            if ($vendorId <= 0) {
                throw new \RuntimeException("Duty vendor ID is required for AP type '{$vendorType}' on truck {$truck->truck_reg}.");
            }

            $vendorExists = match ($vendorType) {
                'customs_authority' => DB::table('duty_vendors')
                    ->where('id', $vendorId)->where('company_id', $cid)->exists(),
                'supplier'          => DB::table('suppliers')
                    ->where('id', $vendorId)->where('company_id', $cid)->exists(),
                'depot'             => DB::table('depots')
                    ->where('id', $vendorId)->where('company_id', $cid)->exists(),
                'transporter'       => DB::table('transporters')
                    ->where('id', $vendorId)->where('company_id', $cid)->exists(),
                default             => false,
            };

            if (! $vendorExists) {
                throw new \RuntimeException("Duty vendor #{$vendorId} (type: {$vendorType}) not found or does not belong to this company. Cannot post duty for truck {$truck->truck_reg}.");
            }
        }

        DB::transaction(function () use (
            $truck, $vendorType, $vendorId, $amount, $currency, $cid, $date, $desc, $userId, $purchase
        ) {
            // Post to correct AP ledger based on vendor type
            if ($vendorType === 'customs_authority' && $vendorId) {
                $alreadyPosted = DutyLedgerEntry::where('ref_type', ImportTruck::class)
                    ->where('ref_id', $truck->id)
                    ->where('type', 'duty_charge')
                    ->exists();

                if (!$alreadyPosted) {
                    DutyLedgerEntry::create([
                        'company_id'    => $cid,
                        'duty_vendor_id'=> $vendorId,
                        'type'          => 'duty_charge',
                        'amount'        => $amount,
                        'currency'      => $currency,
                        'description'   => $desc,
                        'entry_date'    => $date,
                        'ref_type'      => ImportTruck::class,
                        'ref_id'        => $truck->id,
                        'created_by'    => $userId,
                    ]);
                }
            } elseif ($vendorType === 'supplier' && $vendorId) {
                $alreadyPosted = SupplierLedgerEntry::where('ref_type', ImportTruck::class . ':duty')
                    ->where('ref_id', $truck->id)
                    ->where('type', 'purchase_invoice')
                    ->exists();

                if (!$alreadyPosted) {
                    $supplierCurrency = DB::table('suppliers')->where('id', $vendorId)->value('default_currency') ?? $currency;
                    SupplierLedgerEntry::create([
                        'company_id'  => $cid,
                        'supplier_id' => $vendorId,
                        'type'        => 'purchase_invoice',
                        'amount'      => $amount,
                        'currency'    => $supplierCurrency,
                        'description' => $desc,
                        'entry_date'  => $date,
                        'ref_type'    => ImportTruck::class . ':duty',
                        'ref_id'      => $truck->id,
                        'created_by'  => $userId,
                    ]);
                }
            } elseif ($vendorType === 'depot' && $vendorId) {
                $alreadyPosted = DB::table('depot_ledger_entries')
                    ->where('ref_type', ImportTruck::class . ':duty')
                    ->where('ref_id', $truck->id)
                    ->where('type', 'other_charge')
                    ->exists();

                if (!$alreadyPosted) {
                    DB::table('depot_ledger_entries')->insert([
                        'company_id'  => $cid,
                        'depot_id'    => $vendorId,
                        'type'        => 'other_charge',
                        'amount'      => $amount,
                        'currency'    => $currency,
                        'description' => $desc . ' (duty paid via depot)',
                        'entry_date'  => $date,
                        'ref_type'    => ImportTruck::class . ':duty',
                        'ref_id'      => $truck->id,
                        'created_by'  => $userId,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            } elseif ($vendorType === 'transporter' && $vendorId) {
                $alreadyPosted = DB::table('transporter_ledger_entries')
                    ->where('ref_type', ImportTruck::class . ':duty')
                    ->where('ref_id', $truck->id)
                    ->where('type', 'advance')
                    ->exists();

                if (!$alreadyPosted) {
                    DB::table('transporter_ledger_entries')->insert([
                        'company_id'     => $cid,
                        'transporter_id' => $vendorId,
                        'type'           => 'advance',
                        'amount'         => $amount,
                        'currency'       => $currency,
                        'description'    => $desc . ' (duty fronted by transporter)',
                        'entry_date'     => $date,
                        'ref_type'       => ImportTruck::class . ':duty',
                        'ref_id'         => $truck->id,
                        'created_by'     => $userId,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                }
            }

            // Always create batch cost entry (idempotent)
            self::createBatchCostEntry($truck, $amount, $currency, $userId);

            $truck->update([
                'duty_amount'     => $amount,
                'duty_status'     => 'posted',
                'duty_posted_at'  => now(),
            ]);
        });

        return "Duty {$currency} " . number_format($amount, 2) . " posted for {$truck->truck_reg}";
    }

    private static function createBatchCostEntry(ImportTruck $truck, float $amount, string $currency, int $userId): void
    {
        $nomination = $truck->nomination;
        $purchase   = $nomination?->purchase;
        if (!$purchase || !$purchase->batch_id) {
            return;
        }

        $exists = DB::table('batch_costs')
            ->where('truck_id', $truck->id)
            ->where('category', 'duty')
            ->where('purchase_id', $purchase->id)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('batch_costs')->insert([
            'batch_id'            => $purchase->batch_id,
            'purchase_id'         => $purchase->id,
            'nomination_id'       => $nomination->id,
            'truck_id'            => $truck->id,
            'company_id'          => (int) $truck->company_id,
            'category'            => 'duty',
            'description'         => "Duty — truck {$truck->truck_reg}",
            'amount'              => $amount,
            'currency'            => $currency,
            'exchange_rate'       => 1,
            'amount_base'         => $amount,
            'entry_date'          => $truck->border_date?->format('Y-m-d') ?? now()->toDateString(),
            'is_included_in_cost' => false,
            'auto_posted'         => true,
            'created_by'          => $userId,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    /**
     * Resolve the vendor display name for a truck's duty vendor.
     */
    public static function vendorName(ImportTruck $truck): string
    {
        return match ($truck->duty_vendor_type) {
            'customs_authority' => DB::table('duty_vendors')->where('id', $truck->duty_vendor_id)->value('name') ?? 'Customs Authority',
            'supplier'          => DB::table('suppliers')->where('id', $truck->duty_vendor_id)->value('name') ?? 'Supplier',
            'depot'             => DB::table('depots')->where('id', $truck->duty_vendor_id)->value('name') ?? 'Depot',
            'transporter'       => DB::table('transporters')->where('id', $truck->duty_vendor_id)->value('name') ?? 'Transporter',
            'self'              => 'Self (no AP)',
            default             => '—',
        };
    }
}
