<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveCompany;

class DutyLedgerEntry extends Model
{
    use BelongsToActiveCompany;

    protected $table = 'duty_ledger_entries';

    protected $fillable = [
        'company_id',
        'duty_vendor_id',
        'type',
        'amount',
        'currency',
        'description',
        'entry_date',
        'ref_type',
        'ref_id',
        'created_by',
    ];

    protected $casts = [
        'amount'     => 'decimal:4',
        'entry_date' => 'date',
    ];

    public function dutyVendor()
    {
        return $this->belongsTo(DutyVendor::class, 'duty_vendor_id');
    }
}
