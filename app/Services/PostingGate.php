<?php

namespace App\Services;

use App\Models\Company;
use App\Models\InventoryPeriod;

class PostingGate
{
    public function __construct(protected PeriodResolver $periodResolver) {}

    /**
     * Assert that posting is allowed for this company.
     * Throws a RuntimeException with a user-friendly message if blocked.
     */
    public function assertCanPost(int $companyId): InventoryPeriod
    {
        $company = Company::findOrFail($companyId);

        if ($company->inventory_posting_paused) {
            $reason = $company->posting_paused_reason
                ? ' Reason: ' . $company->posting_paused_reason
                : '';
            throw new \RuntimeException(
                'Inventory posting is currently paused for this company.' . $reason
            );
        }

        return $this->periodResolver->resolveOrFail($companyId);
    }

    /**
     * Check without throwing — returns [allowed: bool, period: ?InventoryPeriod, message: ?string]
     */
    public function check(int $companyId): array
    {
        try {
            $period = $this->assertCanPost($companyId);
            return ['allowed' => true, 'period' => $period, 'message' => null];
        } catch (\RuntimeException $e) {
            return ['allowed' => false, 'period' => null, 'message' => $e->getMessage()];
        }
    }
}
