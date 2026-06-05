<?php

namespace App\Models;

use App\Models\Concerns\BelongsToActiveCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchCost extends Model
{
    use BelongsToActiveCompany;

    protected $guarded = [];

    protected $casts = [
        'amount'              => 'float',
        'exchange_rate'       => 'float',
        'amount_base'         => 'float',
        'is_included_in_cost' => 'bool',
        'entry_date'          => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function nomination(): BelongsTo
    {
        return $this->belongsTo(ImportNomination::class, 'nomination_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
