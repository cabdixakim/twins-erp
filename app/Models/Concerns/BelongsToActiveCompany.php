<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait BelongsToActiveCompany
{
    protected static function bootBelongsToActiveCompany(): void
    {
        // Auto-scope all queries to active company (when available)
        static::addGlobalScope('active_company', function (Builder $builder) {
            $companyId = static::activeCompanyId();
            if ($companyId) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $companyId);
            }
        });

        // Auto-assign company_id on create if missing
        static::creating(function (Model $model) {
            if (empty($model->company_id)) {
                $companyId = static::activeCompanyId();
                if ($companyId) {
                    $model->company_id = $companyId;
                }
            }
        });
    }

    protected static function activeCompanyId(): int
    {
        $u = Auth::user();
        return (int) ($u?->active_company_id ?? 0);
    }

    // Optional: admin/system jobs can call Model::withoutCompanyScope()
    public static function withoutCompanyScope(): Builder
    {
        return static::withoutGlobalScope('active_company');
    }
}