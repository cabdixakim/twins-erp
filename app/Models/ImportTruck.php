<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveCompany;

class ImportTruck extends Model
{
    use BelongsToActiveCompany;

    protected $table = 'import_trucks';

    protected $fillable = [
        'company_id',
        'nomination_id',
        'truck_reg',
        'trailer_reg',
        'driver_name',
        'driver_passport',
        'driver_license',
        'driver_phone',
        'capacity',
        'status',
        'qty_loaded',
        'pickup_date',
        'pickup_terminal',
        'load_notes',
        'tr8_number',
        't1_number',
        'border_date',
        'depot_id',
        'qty_delivered',
        'delivery_date',
        'delivery_notes',
        'shortfall_qty',
        'allowed_loss_qty',
        'excess_loss_qty',
        'shortfall_charge',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'capacity'        => 'decimal:3',
        'qty_loaded'      => 'decimal:3',
        'qty_delivered'   => 'decimal:3',
        'shortfall_qty'   => 'decimal:3',
        'allowed_loss_qty' => 'decimal:3',
        'excess_loss_qty' => 'decimal:3',
        'shortfall_charge' => 'decimal:2',
        'pickup_date'     => 'date',
        'border_date'     => 'date',
        'delivery_date'   => 'date',
    ];

    public function nomination()
    {
        return $this->belongsTo(ImportNomination::class, 'nomination_id');
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return match($this->status) {
            'nominated'      => 'Nominated',
            'loading_failed' => 'Load Failed',
            'loaded'         => 'Loaded',
            'in_transit'     => 'In Transit',
            'border_cleared' => 'Border Cleared',
            'delivered'      => 'Delivered',
            default          => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'nominated'      => 's-slate',
            'loading_failed' => 's-rose',
            'loaded'         => 's-blue',
            'in_transit'     => 's-amber',
            'border_cleared' => 's-purple',
            'delivered'      => 's-green',
            default          => 's-slate',
        };
    }

    public function nextActions(): array
    {
        return match($this->status) {
            'nominated'      => ['record_load', 'fail_load'],
            'loading_failed' => [],
            'loaded'         => ['mark_in_transit'],
            'in_transit'     => ['record_border'],
            'border_cleared' => ['record_delivery'],
            'delivered'      => [],
            default          => [],
        };
    }
}
