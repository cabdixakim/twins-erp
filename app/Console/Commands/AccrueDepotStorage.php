<?php

namespace App\Console\Commands;

use App\Models\Depot;
use App\Services\DepotStorageAccrual;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AccrueDepotStorage extends Command
{
    protected $signature = 'depot:accrue-storage
        {--month= : Month (1-12), defaults to previous month}
        {--year=  : Year (YYYY), defaults to year of previous month}
        {--depot= : Specific depot ID (optional, runs all if omitted)}
        {--previous-month : Use previous calendar month (used by scheduler)}
        {--dry-run : Preview without posting}';

    protected $description = 'Post monthly storage charges for all active depot charge configs';

    public function handle(): int
    {
        $ref   = ($this->option('previous-month') || (!$this->option('month') && !$this->option('year')))
                 ? now()->subMonth()
                 : now();
        $month = (int) ($this->option('month') ?: $ref->month);
        $year  = (int) ($this->option('year')  ?: $ref->year);
        $period = sprintf('%04d-%02d', $year, $month);

        $this->info("Running storage accrual for period: {$period}");
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN — no charges will be posted.');
        }

        // Build depot query
        $q = Depot::query()
            ->where('is_active', true)
            ->where(function ($q) { $q->whereNull('is_system')->orWhere('is_system', false); })
            ->whereExists(function ($sub) use ($year, $month) {
                $periodDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
                $sub->select(DB::raw(1))
                    ->from('depot_charge_configs')
                    ->whereColumn('depot_charge_configs.depot_id', 'depots.id')
                    ->where('depot_charge_configs.is_active', true)
                    ->where('depot_charge_configs.category', 'storage')
                    ->where('depot_charge_configs.rate_unit', 'per_m3_per_month')
                    ->where('depot_charge_configs.effective_from', '<=', $periodDate)
                    ->where(function ($q2) use ($periodDate) {
                        $q2->whereNull('depot_charge_configs.effective_to')
                           ->orWhere('depot_charge_configs.effective_to', '>=', $periodDate);
                    });
            });

        if ($depotId = $this->option('depot')) {
            $q->where('id', (int) $depotId);
        }

        $depots = $q->get();

        if ($depots->isEmpty()) {
            $this->warn('No depots with active storage configs found.');
            return self::SUCCESS;
        }

        $total = 0;

        foreach ($depots as $depot) {
            $cid = (int) $depot->company_id;
            $this->line("  Depot: {$depot->name} (company #{$cid})");

            if ($this->option('dry-run')) {
                $preview = DepotStorageAccrual::preview($depot, $year, $month, $cid);
                foreach ($preview['rows'] as $r) {
                    if ($r['chargeable'] && !$r['already_posted']) {
                        $this->line("    → {$r['product']}: {$r['currency']} " . number_format($r['amount'], 2));
                        $total++;
                    }
                }
            } else {
                $posted = DepotStorageAccrual::postForDepot($depot, $year, $month, $cid, 1);
                foreach ($posted as $line) {
                    $this->line("    ✓ {$line}");
                    $total++;
                }
                if (empty($posted)) {
                    $this->line('    — nothing new to post (already done or no stock)');
                }
            }
        }

        $this->info("Done. {$total} charge(s) " . ($this->option('dry-run') ? 'previewed' : 'posted') . ".");
        return self::SUCCESS;
    }
}
