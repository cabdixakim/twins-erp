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
        'logo_path',
        'country',
        'timezone',
        'costing_method',
        'inventory_posting_paused',
        'posting_paused_at',
        'posting_paused_by',
        'posting_paused_reason',
    ];

    protected $casts = [
        'inventory_posting_paused' => 'boolean',
        'posting_paused_at'        => 'datetime',
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



