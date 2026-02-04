<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToActiveCompany;

class Supplier extends Model
{
    use HasFactory, BelongsToActiveCompany;

    protected $fillable = [
        'name',
        'type',
        'country',
        'city',
        'contact_person',
        'phone',
        'email',
        'default_currency',
        'is_active',
        'notes',
        'company_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    
      public function company()
    {
        return $this->belongsTo(Company::class);
    }

}