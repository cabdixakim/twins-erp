<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveCompany;

class DutyVendor extends Model
{
    use BelongsToActiveCompany;

    protected $table = 'duty_vendors';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'country',
        'city',
        'contact_person',
        'phone',
        'default_currency',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ledgerEntries()
    {
        return $this->hasMany(DutyLedgerEntry::class, 'duty_vendor_id');
    }

    public function balance(): float
    {
        return (float) $this->ledgerEntries()->sum('amount');
    }
}
