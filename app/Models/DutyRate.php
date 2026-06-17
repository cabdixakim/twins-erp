<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToActiveCompany;

class DutyRate extends Model
{
    use BelongsToActiveCompany;

    protected $table = 'duty_rates';

    protected $fillable = [
        'company_id',
        'product_id',
        'rate_per_1000l',
        'currency',
        'effective_from',
        'effective_to',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'rate_per_1000l' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'is_active'      => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function isActiveOn(string $date): bool
    {
        if (!$this->is_active) {
            return false;
        }
        $d = \Carbon\Carbon::parse($date);
        if ($d->lt($this->effective_from)) {
            return false;
        }
        if ($this->effective_to && $d->gt($this->effective_to)) {
            return false;
        }
        return true;
    }
}
