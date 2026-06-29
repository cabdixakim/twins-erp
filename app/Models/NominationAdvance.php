<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NominationAdvance extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount'      => 'decimal:2',
        'advance_date'=> 'date',
        'voided_at'   => 'datetime',
    ];

    public function nomination()
    {
        return $this->belongsTo(ImportNomination::class, 'nomination_id');
    }

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voider()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
