<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'qty_purchased'  => 'float',
        'qty_received'   => 'float',
        'qty_remaining'  => 'float',
        'total_cost'     => 'float',
        'unit_cost'      => 'float',
        'purchased_at'   => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function depotStocks(): HasMany
    {
        return $this->hasMany(DepotStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(InventoryConsumption::class);
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