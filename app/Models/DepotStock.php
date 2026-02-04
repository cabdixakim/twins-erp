<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepotStock extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'qty_on_hand' => 'float',
        'qty_reserved'=> 'float',
        'unit_cost'   => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function scopeForCompany(Builder $q, int $companyId): Builder
    {
        return $q->where('company_id', $companyId);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $model = parent::resolveRouteBinding($value, $field);
        if (!$model) return null;

        $user = auth()->user();
        $activeCompanyId = (int) ($user?->active_company_id ?? 0);
        if (!$activeCompanyId) return $model;

        return ((int) $model->company_id === $activeCompanyId) ? $model : null;
    }
}