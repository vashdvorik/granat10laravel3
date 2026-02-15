<?php
// app/Console/Commands/DeactivateExpiredPackages.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SellerPackage;
use Illuminate\Support\Facades\DB;

class DeactivateExpiredPackages extends Command
{
    protected $signature = 'packages:deactivate-expired {--auto-renew : Попытаться автопродлить из баланса}';
    
    protected $description = 'Деактивирует просроченные пакеты продавцов и пытается автоматически продлить';

    public function handle()
    {
        $autoRenew = $this->option('auto-renew');
        
        $this->info('🔄 Проверка просроченных пакетов...');
        
        // Находим просроченные активные пакеты
        $expiredPackages = SellerPackage::where('is_active', true)
            ->whereNotNull('end_date')
            ->where('end_date', '<', now())
            ->with(['seller', 'package'])
            ->get();

        if ($expiredPackages->isEmpty()) {
            $this->info('✅ Просроченных пакетов не найдено');
            return 0;
        }

        $this->warn("⚠️  Найдено просроченных пакетов: {$expiredPackages->count()}");
        
        $deactivated = 0;
        $renewed = 0;
        $failed = 0;

        foreach ($expiredPackages as $sellerPackage) {
            $seller = $sellerPackage->seller;
            $package = $sellerPackage->package;
            
            $this->line("\n📦 Пакет #{$sellerPackage->id}: {$package->name}");
            $this->line("   Продавец: {$seller->name} (ID: {$seller->id})");
            $this->line("   Истек: {$sellerPackage->end_date->format('d.m.Y')}");
            $this->line("   Баланс: {$seller->balance} руб, Цена: {$package->price} руб");
            
            // Попытка автопродления
            if ($autoRenew && $seller->auto_renew && $seller->balance >= $package->price) {
                try {
                    // ✅ Используем транзакцию для защиты денег
                    DB::transaction(function () use ($seller, $sellerPackage, $package) {
                        // Списываем с баланса
                        $seller->decrement('balance', $package->price);
                        
                        // Продлеваем пакет
                        $sellerPackage->update([
                            'start_date' => now(),
                            'end_date' => now()->addDays($package->duration_days),
                        ]);
                    });
                    
                    $this->info("   ✅ Автопродлен до {$sellerPackage->end_date->format('d.m.Y')}");
                    $renewed++;
                    
                    // TODO: Отправить уведомление продавцу об успешном продлении
                    
                } catch (\Exception $e) {
                    $this->error("   ❌ Ошибка при продлении: {$e->getMessage()}");
                    $failed++;
                }
                
            } else {
                // Деактивируем пакет
                $sellerPackage->update(['is_active' => false]);
                
                // Отключаем прием заказов, если пакет это разрешал
                if ($package->has_order_system && $seller->can_order) {
                    $seller->update(['can_order' => false]);
                    $this->line("   🔒 Прием заказов отключен");
                }
                
                $reason = match(true) {
                    !$autoRenew => "автопродление не включено в команде",
                    !$seller->auto_renew => "продавец отключил автопродление",
                    $seller->balance < $package->price => "недостаточно средств (баланс: {$seller->balance} руб)",
                    default => "неизвестная причина"
                };
                
                $this->warn("   ⚠️  Пакет деактивирован: {$reason}");
                $deactivated++;
                
                // TODO: Отправить уведомление продавцу о деактивации
            }
        }

        // Итоги
        $this->newLine();
        $this->info("╔═══════════════════════════╗");
        $this->info("║       ИТОГИ РАБОТЫ        ║");
        $this->info("╠═══════════════════════════╣");
        $this->info("║ Продлено:        {$renewed} шт     ║");
        $this->info("║ Деактивировано:  {$deactivated} шт     ║");
        if ($failed > 0) {
            $this->error("║ Ошибок:          {$failed} шт     ║");
        }
        $this->info("╚═══════════════════════════╝");
        
        return 0;
    }
}