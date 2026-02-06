<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveCompany;

class Depot extends Model
{
    use HasFactory, BelongsToActiveCompany;

    protected $fillable = [
        'name',
        'city',
        'storage_fee_per_1000_l',
        'default_shrinkage_pct',
        'is_active',
        'notes',
        'company_id',
        'is_system',
    ];

    protected $casts = [
        'storage_fee_per_1000_l' => 'decimal:4',
        'default_shrinkage_pct'  => 'decimal:4',
        'is_active'              => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }


}