<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToActiveCompany;

class Client extends Model
{
    use HasFactory, BelongsToActiveCompany;

    protected $guarded = [];

    protected $casts = [
        'is_active'    => 'boolean',
        'credit_limit' => 'float',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
