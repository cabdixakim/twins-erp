<?php
namespace App\Console;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
class Kernel extends ConsoleKernel
{
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }

    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
    {
        // 2nd of each month at 00:05 — posts previous month's storage for all depots.
        // The 2nd gives the 1st's transactions time to finalise before we accrue.
        $schedule->command('depot:accrue-storage --previous-month')
                 ->monthlyOn(2, '00:05')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/depot-storage-accrual.log'));
    }
}
