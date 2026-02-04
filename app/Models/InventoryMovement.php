<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BelongsToActiveCompany;

class InventoryMovement extends Model
{
    use HasFactory, BelongsToActiveCompany;

    protected $guarded = [];

    protected $casts = [
        'qty'        => 'float',
        'unit_cost'  => 'float',
        'total_cost' => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function fromDepot(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'from_depot_id');
    }

    public function toDepot(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'to_depot_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}