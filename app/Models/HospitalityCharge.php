<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveCompany;

class HospitalityCharge extends Model
{
    use BelongsToActiveCompany;

    protected $table = 'hospitality_charges';

    protected $fillable = [
        'company_id',
        'purchase_id',
        'paid_to_type',
        'paid_to_id',
        'paid_to_name',
        'amount',
        'currency',
        'exchange_rate',
        'amount_base',
        'entry_date',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount'        => 'decimal:4',
        'exchange_rate' => 'decimal:6',
        'amount_base'   => 'decimal:4',
        'entry_date'    => 'date',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
