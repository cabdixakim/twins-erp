<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Depot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'storage_fee_per_1000_l',
        'default_shrinkage_pct',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'storage_fee_per_1000_l' => 'decimal:4',
        'default_shrinkage_pct'  => 'decimal:4',
        'is_active'              => 'boolean',
    ];
}