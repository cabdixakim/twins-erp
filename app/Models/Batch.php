<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BelongsToActiveCompany;

class Batch extends Model
{
    use HasFactory, BelongsToActiveCompany;

    protected $guarded = [];

    protected $casts = [
        'qty_purchased'  => 'float',
        'qty_received'   => 'float',
        'qty_remaining'  => 'float',
        'total_cost'     => 'float',
        'unit_cost'      => 'float',
        'purchased_at'   => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function depotStocks(): HasMany
    {
        return $this->hasMany(DepotStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(InventoryConsumption::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}