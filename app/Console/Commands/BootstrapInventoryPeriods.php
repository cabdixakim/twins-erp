<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\InventoryConsumption;
use App\Models\InventoryPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BootstrapInventoryPeriods extends Command
{
    protected $signature   = 'inventory:bootstrap-periods';
    protected $description = 'Create Period 1 for companies with existing movements and stamp all movements/consumptions with it.';

    public function handle(): int
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $hasMovements = InventoryMovement::where('company_id', $company->id)->exists();
            $hasPeriod    = InventoryPeriod::where('company_id', $company->id)->exists();

            if (!$hasMovements) {
                $this->line("  [skip] {$company->name} — no movements, period will auto-create on first posting.");
                continue;
            }

            if ($hasPeriod) {
                $this->line("  [skip] {$company->name} — already has a period.");
                continue;
            }

            DB::transaction(function () use ($company) {
                // Find earliest movement to anchor the period start
                $earliest = InventoryMovement::where('company_id', $company->id)
                    ->min('created_at');

                $period = InventoryPeriod::create([
                    'company_id'     => $company->id,
                    'name'           => 'Period 1',
                    'costing_method' => $company->costing_method ?? 'weighted_average',
                    'starts_at'      => $earliest ?? now(),
                    'ends_at'        => null,
                    'status'         => 'open',
                    'created_by'     => null,
                ]);

                // Stamp all existing movements
                InventoryMovement::where('company_id', $company->id)
                    ->whereNull('period_id')
                    ->update(['period_id' => $period->id]);

                // Stamp all existing consumptions
                InventoryConsumption::where('company_id', $company->id)
                    ->whereNull('period_id')
                    ->update(['period_id' => $period->id]);

                $movementCount    = InventoryMovement::where('company_id', $company->id)->count();
                $consumptionCount = InventoryConsumption::where('company_id', $company->id)->count();

                $this->info("  [done] {$company->name} — Period 1 created, {$movementCount} movements + {$consumptionCount} consumptions stamped.");
            });
        }

        $this->info('Bootstrap complete.');
        return self::SUCCESS;
    }
}
