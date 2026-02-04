<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToActiveCompany;

class Transporter extends Model
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
        'default_rate_per_1000_l',
        'payment_terms',
        'is_active',
        'notes',
        'company_id',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'default_rate_per_1000_l' => 'decimal:4',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

}