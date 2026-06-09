<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount'        => 'float',
        'exchange_rate' => 'float',
        'entry_date'    => 'date',
        'voided_at'     => 'datetime',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transferAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'transfer_account_id');
    }

    public function transferTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class, 'transfer_transaction_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function signedAmount(): float
    {
        return in_array($this->type, ['deposit', 'transfer_in']) ? $this->amount : -$this->amount;
    }
}
