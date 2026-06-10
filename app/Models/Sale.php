<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Concerns\BelongsToActiveCompany;

class Sale extends Model
{
    use HasFactory, BelongsToActiveCompany;

    protected $guarded = [];

    protected $casts = [
        'sale_date'     => 'date',
        'qty'           => 'float',
        'unit_price'    => 'float',
        'total'         => 'float',
        'cogs_total'    => 'float',
        'gross_profit'   => 'float',
        'freight_amount' => 'float',
        'temperature'    => 'float',
        'density'        => 'float',
        'qty_delivered'  => 'float',
        'pod_received_at' => 'date',
        'posted_at'      => 'datetime',
    ];

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transporter(): BelongsTo
    {
        return $this->belongsTo(Transporter::class);
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'inventory_movement_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}