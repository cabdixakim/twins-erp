<?php

namespace App\Models;

use App\Models\Concerns\BelongsToActiveCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    use HasFactory, BelongsToActiveCompany;

    protected $guarded = [];

    protected $casts = [
        'purchase_date' => 'date',
        'qty'           => 'float',
        'unit_price'    => 'float',
        'reference'     => 'string',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDisplayRefAttribute(): string
    {
        $companyCode = $this->company?->code ?? '';
        $year = $this->purchase_date ? $this->purchase_date->format('Y') : now()->format('Y');
        $seq = $this->sequence_no ? str_pad((string)$this->sequence_no, 5, '0', STR_PAD_LEFT) : $this->id;
        if ($companyCode) {
            return "PO-{$companyCode}-{$year}-{$seq}";
        }
        return $this->reference ?? $this->id;
    }
}