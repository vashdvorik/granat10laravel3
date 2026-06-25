<?php

namespace App\Console;

use App\Jobs\RecordQueueWorkerHeartbeat;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Проверка и автопродление пакетов каждый день в 01:00
        $schedule->command('packages:deactivate-expired --auto-renew')
            ->dailyAt('01:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/packages-check.log'));

        $schedule->command('orders:anonymize-expired')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/orders-anonymization.log'));

        if (config('queue.default') !== 'sync') {
            $schedule->job(new RecordQueueWorkerHeartbeat)->everyMinute();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
