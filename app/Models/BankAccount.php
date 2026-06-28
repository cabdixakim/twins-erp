<?php

namespace App\Models;

use App\Models\Concerns\BelongsToActiveCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use BelongsToActiveCompany;

    protected $guarded = [];

    protected $casts = [
        'opening_balance' => 'float',
        'is_active'       => 'bool',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'gl_account_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function currentBalance(): float
    {
        $posted = $this->transactions()
            ->whereNull('voided_at')
            ->selectRaw("SUM(CASE WHEN type IN ('deposit','transfer_in') THEN amount ELSE -amount END) as signed_total")
            ->value('signed_total');

        return (float) $this->opening_balance + (float) $posted;
    }
}
