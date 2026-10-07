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
        // Снятие VIP с товаров, у которых истёк оплаченный срок
        $schedule->command('products:expire-vip')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/vip-expiration.log'));

        // Сверка ожидающих пополнений баланса с банком (если ResultURL не дошёл)
        $schedule->command('balance:reconcile-topups')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/balance-topups.log'));

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
