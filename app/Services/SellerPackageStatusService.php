<?php

namespace App\Services;

use App\Models\Seller;

class SellerPackageStatusService
{
    public function getStatus(Seller $seller): array
    {
        $activePackage = $seller->activePackage();

        if (!$activePackage) {
            $activeProductsCount = $seller->products()->where('is_active', true)->count();

            return [
                'has_package' => false,
                'can_auto_renew' => false,
                'message' => 'У вас нет активного пакета',
                'description' => $activeProductsCount > 0
                    ? "Ваши товары ({$activeProductsCount} шт.) скрыты от покупателей"
                    : 'Приобретите пакет для публикации товаров',
                'alert_type' => 'danger',
            ];
        }

        $activePackage->loadMissing('package');
        $seller->loadMissing('user');
        $package = $activePackage->package;

        if (!$activePackage->end_date) {
            return [
                'has_package' => true,
                'package_name' => $package->name,
                'message' => "Пакет \"{$package->name}\" активен",
                'description' => 'Ваш пакет бессрочный и не требует автопродления',
                'alert_type' => 'success',
            ];
        }

        $daysLeft = (int) now()->diffInDays($activePackage->end_date, false);
        $balance = $seller->user?->balance ?? 0;
        $canAutoRenew = $balance >= $package->price;

        if ($daysLeft < 0) {
            return [
                'has_package' => false,
                'can_auto_renew' => $canAutoRenew,
                'message' => 'Пакет просрочен',
                'description' => 'Ваши товары скрыты от покупателей',
                'balance' => $balance,
                'price' => $package->price,
                'package_name' => $package->name,
                'alert_type' => 'danger',
            ];
        }

        if ($daysLeft <= 7) {
            return $this->expiringStatus($seller, $package->name, $package->price, $activePackage->end_date, $daysLeft, $balance, $canAutoRenew);
        }

        return [
            'has_package' => true,
            'days_left' => $daysLeft,
            'end_date' => $activePackage->end_date,
            'package_name' => $package->name,
            'message' => "Пакет \"{$package->name}\" активен",
            'description' => "Действует до {$activePackage->end_date->format('d.m.Y')} ({$daysLeft} дней)",
            'alert_type' => 'info',
        ];
    }

    public function daysWord(int $days): string
    {
        $cases = [2, 0, 1, 1, 1, 2];
        $titles = ['день', 'дня', 'дней'];

        return $titles[($days % 100 > 4 && $days % 100 < 20) ? 2 : $cases[min($days % 10, 5)]];
    }

    private function expiringStatus(
        Seller $seller,
        string $packageName,
        int|float|string $price,
        mixed $endDate,
        int $daysLeft,
        int|float|string $balance,
        bool $canAutoRenew
    ): array {
        $description = match (true) {
            !$seller->auto_renew => ' Автопродление отключено.',
            !$canAutoRenew => "❌ Недостаточно средств для автопродления. Нужно {$price} руб, баланс: {$balance} руб",
            default => "Пакет будет автоматически продлен из вашего баланса. Ваш баланс: {$balance} руб, стоимость продления: {$price} руб",
        };

        return [
            'has_package' => true,
            'days_left' => $daysLeft,
            'end_date' => $endDate,
            'can_auto_renew' => $canAutoRenew && $seller->auto_renew,
            'message' => "Пакет истекает через {$daysLeft} ".$this->daysWord($daysLeft),
            'description' => $description,
            'balance' => $balance,
            'price' => $price,
            'package_name' => $packageName,
            'alert_type' => $seller->auto_renew && $canAutoRenew ? 'warning' : 'danger',
        ];
    }
}
