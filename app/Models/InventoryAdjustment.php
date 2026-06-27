<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'qty'        => 'float',
        'unit_cost'  => 'float',
        'total_value'=> 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(InventoryPeriod::class);
    }

    public static function reasonLabel(string $type): string
    {
        return match($type) {
            'depot_shrinkage'        => 'Depot Shrinkage',
            'write_off'              => 'Write-off',
            'meter_variance'         => 'Meter Variance',
            'stock_count_correction' => 'Stock Count Correction',
            'transit_loss'           => 'Transit Loss',
            default                  => ucfirst(str_replace('_', ' ', $type)),
        };
    }
}
