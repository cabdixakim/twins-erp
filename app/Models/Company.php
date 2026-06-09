<?php
// app/Models/Company.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'base_currency',
        'volume_unit',
        'logo_path',
        'country',
        'timezone',
        'address',
        'phone',
        'email',
        'website',
        'rccm',
        'id_nat',
        'nif',
        'costing_method',
        'weighted_avg_cost',
        'accounting_enabled',
        'inventory_periods_enabled',
        'inventory_posting_paused',
        'posting_paused_at',
        'posting_paused_by',
        'posting_paused_reason',
        'invoice_accent_color',
        'invoice_payment_days',
        'invoice_prefix',
        'invoice_tax_rate',
        'invoice_footer_notes',
        'invoice_bank_details',
    ];

    protected $casts = [
        'inventory_posting_paused'  => 'boolean',
        'posting_paused_at'         => 'datetime',
        'weighted_avg_cost'         => 'float',
        'accounting_enabled'        => 'boolean',
        'inventory_periods_enabled' => 'boolean',
    ];


 public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function depots()
    {
        return $this->hasMany(Depot::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function transporters()
    {
        return $this->hasMany(Transporter::class);
    }

    public function inventoryPeriods()
    {
        return $this->hasMany(\App\Models\InventoryPeriod::class);
    }

    public function openPeriod()
    {
        return $this->hasOne(\App\Models\InventoryPeriod::class)->where('status', 'open');
    }

    public function hasInventoryMovements(): bool
    {
        return \App\Models\InventoryMovement::where('company_id', $this->id)->exists();
    }

    public function canChangeCosting(): bool
    {
        return !$this->hasInventoryMovements();
    }
}



