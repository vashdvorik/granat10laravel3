<?php

namespace App\Console\Commands;

use App\Models\SellerPackage;
use App\Services\PackageService;
use Illuminate\Console\Command;
use Throwable;

class DeactivateExpiredPackages extends Command
{
    protected $signature = 'packages:deactivate-expired {--auto-renew : Попытаться автопродлить из баланса}';

    protected $description = 'Деактивирует просроченные пакеты продавцов и пытается автоматически продлить';

    public function handle(PackageService $packageService): int
    {
        $autoRenew = (bool) $this->option('auto-renew');

        $this->info('Проверка просроченных пакетов...');

        $expiredPackages = SellerPackage::where('is_active', true)
            ->whereNotNull('end_date')
            ->where('end_date', '<', now()->toDateString())
            ->with(['seller.user', 'package'])
            ->get();

        if ($expiredPackages->isEmpty()) {
            $this->info('Просроченных пакетов не найдено');

            return self::SUCCESS;
        }

        $this->warn("Найдено просроченных пакетов: {$expiredPackages->count()}");

        $deactivated = 0;
        $renewed = 0;
        $failed = 0;

        foreach ($expiredPackages as $sellerPackage) {
            $seller = $sellerPackage->seller;
            $package = $sellerPackage->package;
            $balance = $seller->user?->balance ?? 0;

            $this->line("Пакет #{$sellerPackage->id}: {$package->name}");
            $this->line("Продавец: {$seller->name} (ID: {$seller->id})");
            $this->line("Истек: {$sellerPackage->end_date->format('d.m.Y')}");
            $this->line("Баланс: {$balance} руб, цена: {$package->price} руб");

            if ($autoRenew && $packageService->canAutoRenew($sellerPackage)) {
                try {
                    $renewedPackage = $packageService->renew($sellerPackage);
                    $this->info("Автопродлен до {$renewedPackage->end_date->format('d.m.Y')}");
                    $renewed++;
                } catch (Throwable $e) {
                    $this->error("Ошибка при продлении: {$e->getMessage()}");
                    $failed++;
                }

                continue;
            }

            $packageService->deactivateExpired($sellerPackage);
            $reason = $packageService->autoRenewBlockReason($sellerPackage, $autoRenew);

            if ($package->has_order_system && $seller->can_order) {
                $this->line('Прием заказов отключен');
            }

            $this->warn("Пакет деактивирован: {$reason}");
            $deactivated++;
        }

        $this->newLine();
        $this->info("Продлено: {$renewed}");
        $this->info("Деактивировано: {$deactivated}");

        if ($failed > 0) {
            $this->error("Ошибок: {$failed}");
        }

        return self::SUCCESS;
    }
}
