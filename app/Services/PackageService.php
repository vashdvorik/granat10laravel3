<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Package;
use App\Models\Seller;
use App\Models\SellerPackage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для работы с пакетами продавцов
 */
class PackageService
{
    /**
     * Активация пакета для продавца
     */
    public function activatePackage(Seller $seller, Package $package): SellerPackage
    {
        return DB::transaction(function () use ($seller, $package) {
            // Деактивация текущего пакета если есть
            $this->deactivateCurrentPackage($seller);

            // Создание новой подписки
            $sellerPackage = new SellerPackage;
            $sellerPackage->seller_id = $seller->id;
            $sellerPackage->package_id = $package->id;
            $sellerPackage->start_date = now();
            $sellerPackage->end_date = now()->addDays($package->duration_days);
            $sellerPackage->is_active = true;
            $sellerPackage->save();

            return $sellerPackage;
        });
    }

    /**
     * Деактивация текущего пакета продавца
     */
    public function deactivateCurrentPackage(Seller $seller): void
    {
        SellerPackage::where('seller_id', $seller->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * Продление пакета
     */
    public function extendPackage(SellerPackage $sellerPackage, int $days): SellerPackage
    {
        $sellerPackage->end_date = $sellerPackage->end_date->addDays($days);
        $sellerPackage->save();

        return $sellerPackage;
    }

    /**
     * Проверка и деактивация истекших пакетов
     */
    public function deactivateExpiredPackages(): int
    {
        return SellerPackage::where('is_active', true)
            ->where('end_date', '<', Carbon::now())
            ->update(['is_active' => false]);
    }

    /**
     * Получение активного пакета продавца
     */
    public function getActivePackage(Seller $seller): ?SellerPackage
    {
        return $seller->activePackage();
    }

    /**
     * Проверка наличия активного пакета
     */
    public function hasActivePackage(Seller $seller): bool
    {
        return $this->getActivePackage($seller) !== null;
    }

    /**
     * Получение доступных пакетов для покупки
     */
    public function getAvailablePackages()
    {
        return Package::where('is_active', true)
            ->orderBy('price')
            ->get();
    }

    /**
     * Проверка возможности активации продукта
     */
    public function canActivateProduct(Seller $seller): bool
    {
        $package = $this->getActivePackage($seller);

        if (! $package) {
            return false;
        }

        $activeProductsCount = $seller->products()
            ->where('is_active', true)
            ->count();

        return $activeProductsCount < $package->package->active_products_limit;
    }

    /**
     * Проверка возможности активации VIP продукта
     */
    public function canActivateVipProduct(Seller $seller): bool
    {
        $package = $this->getActivePackage($seller);

        if (! $package) {
            return false;
        }

        $vipProductsCount = $seller->products()
            ->where('is_active', true)
            ->where('is_vip', true)
            ->count();

        return $vipProductsCount < $package->package->vip_products_limit;
    }

    /**
     * Получение статистики использования пакета
     */
    public function getPackageUsageStats(Seller $seller): array
    {
        $package = $this->getActivePackage($seller);

        if (! $package) {
            return [
                'has_package' => false,
                'products_used' => 0,
                'products_limit' => 0,
                'vip_products_used' => 0,
                'vip_products_limit' => 0,
                'days_left' => 0,
            ];
        }

        $activeProductsCount = $seller->products()
            ->where('is_active', true)
            ->count();

        $vipProductsCount = $seller->products()
            ->where('is_active', true)
            ->where('is_vip', true)
            ->count();

        $daysLeft = Carbon::now()->diffInDays($package->end_date, false);

        return [
            'has_package' => true,
            'package_name' => $package->package->name,
            'products_used' => $activeProductsCount,
            'products_limit' => $package->package->active_products_limit,
            'products_available' => max(0, $package->package->active_products_limit - $activeProductsCount),
            'vip_products_used' => $vipProductsCount,
            'vip_products_limit' => $package->package->vip_products_limit,
            'vip_products_available' => max(0, $package->package->vip_products_limit - $vipProductsCount),
            'expires_at' => $package->end_date,
            'days_left' => max(0, $daysLeft),
        ];
    }
}
