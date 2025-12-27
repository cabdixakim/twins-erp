<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
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