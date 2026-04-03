<?php

namespace App\Services;

use App\Models\Company;
use App\Models\InventoryPeriod;

class PeriodResolver
{
    /**
     * Return the current open period for the company.
     * If none exists and the company has never posted any movements,
     * auto-create Period 1 using the company's costing method.
     *
     * Returns null only if posting is not possible (paused, or ambiguous state).
     */
    public function resolve(int $companyId): ?InventoryPeriod
    {
        $period = InventoryPeriod::query()
            ->where('company_id', $companyId)
            ->where('status', 'open')
            ->first();

        if ($period) {
            return $period;
        }

        $company = Company::findOrFail($companyId);

        $hasMovements = \App\Models\InventoryMovement::query()
            ->where('company_id', $companyId)
            ->exists();

        if ($hasMovements) {
            return null;
        }

        return $this->createFirstPeriod($company);
    }

    /**
     * Auto-create Period 1 for a company that has no movements yet.
     */
    public function createFirstPeriod(Company $company): InventoryPeriod
    {
        return InventoryPeriod::create([
            'company_id'     => $company->id,
            'name'           => 'Period 1',
            'costing_method' => $company->costing_method ?? 'weighted_average',
            'starts_at'      => now(),
            'ends_at'        => null,
            'status'         => 'open',
            'created_by'     => null,
        ]);
    }

    /**
     * Get the open period or throw if none found.
     */
    public function resolveOrFail(int $companyId): InventoryPeriod
    {
        $period = $this->resolve($companyId);

        if (!$period) {
            throw new \RuntimeException(
                'No open inventory period found for this company. ' .
                'Please open a new inventory period before posting.'
            );
        }

        return $period;
    }
}
