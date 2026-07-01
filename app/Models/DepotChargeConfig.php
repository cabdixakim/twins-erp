<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveCompany;

class DepotChargeConfig extends Model
{
    use BelongsToActiveCompany;

    protected $guarded = [];

    protected $casts = [
        'rate'         => 'decimal:6',
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'is_active'      => 'boolean',
    ];

    public function depot()
    {
        return $this->belongsTo(Depot::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // ── Label helpers ────────────────────────────────────────────────────────

    public static function categoryLabel(string $cat): string
    {
        return match($cat) {
            'storage'   => 'Storage',
            'offloading'=> 'Offloading',
            'duty'      => 'Duty',
            'customs'   => 'Customs',
            'other'     => 'Other',
            default     => ucfirst($cat),
        };
    }

    public static function rateUnitLabel(string $unit): string
    {
        return match($unit) {
            'per_m3_per_month' => '/ m³ / month',
            'per_m3'           => '/ m³',
            'per_trip'         => '/ trip',
            'lump_sum'         => 'lump sum / month',
            default            => $unit,
        };
    }

    public static function receiptRuleLabel(?string $rule): string
    {
        return match($rule) {
            'include_receipt_month'  => 'Charge from receipt month',
            'exclude_receipt_month'  => 'Skip receipt month (charge from month 2)',
            'prorate_receipt_month'  => 'Prorate receipt month (days remaining)',
            'exclude_first_30_days'  => 'Exclude first 30 days',
            null                     => '—',
            default                  => $rule,
        };
    }

    public static function paidByLabel(?string $type, ?string $name): string
    {
        return match($type) {
            'exempt' => 'Exempt (no charge)',
            default  => 'Payable to depot',
        };
    }

    // ── Resolve active configs for a depot on a given date ──────────────────

    public static function activeForDepot(int $depotId, int $companyId, string $date): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('company_id', $companyId)
            ->where('depot_id', $depotId)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->orderBy('category')
            ->orderBy('effective_from')
            ->get();
    }
}
