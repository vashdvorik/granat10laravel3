<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PackageService
{
    public function __construct(private readonly ProductLimitService $productLimitService)
    {
    }

    public function purchase(User $user, Seller $seller, Package $package): SellerPackage
    {
        return DB::transaction(function () use ($user, $seller, $package) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            $this->ensureEnoughBalance($lockedUser, $package);
            $this->ensureSellerFitsPackage($seller, $package);

            $seller->activePackage()?->update(['is_active' => false]);

            $sellerPackage = $seller->sellerPackages()->create([
                'package_id' => $package->id,
                'start_date' => now(),
                'end_date' => $this->calculateEndDate($package),
                'is_active' => true,
            ]);

            $this->chargePackage($lockedUser, $package);

            return $sellerPackage;
        });
    }

    public function renew(SellerPackage $sellerPackage): SellerPackage
    {
        return DB::transaction(function () use ($sellerPackage) {
            $sellerPackage->loadMissing(['seller.user', 'package']);

            $user = User::query()->lockForUpdate()->findOrFail($sellerPackage->seller->user_id);
            $package = $sellerPackage->package;

            $this->ensureEnoughBalance($user, $package);
            $this->chargePackage($user, $package);

            $sellerPackage->update([
                'start_date' => now(),
                'end_date' => $this->calculateEndDate($package),
                'is_active' => true,
            ]);

            return $sellerPackage->refresh();
        });
    }

    public function deactivateExpired(SellerPackage $sellerPackage): void
    {
        $sellerPackage->loadMissing(['seller', 'package']);

        $sellerPackage->update(['is_active' => false]);

        if ($sellerPackage->package->has_order_system && $sellerPackage->seller->can_order) {
            $sellerPackage->seller->forceFill(['can_order' => false])->save();
        }
    }

    public function canAutoRenew(SellerPackage $sellerPackage): bool
    {
        $sellerPackage->loadMissing(['seller.user', 'package']);

        return (bool) $sellerPackage->seller->auto_renew
            && $sellerPackage->seller->user
            && $sellerPackage->seller->user->balance >= $sellerPackage->package->price;
    }

    public function autoRenewBlockReason(SellerPackage $sellerPackage, bool $autoRenewEnabled): string
    {
        $sellerPackage->loadMissing(['seller.user', 'package']);

        return match (true) {
            !$autoRenewEnabled => 'автопродление не включено в команде',
            !$sellerPackage->seller->auto_renew => 'продавец отключил автопродление',
            !$sellerPackage->seller->user => 'у продавца нет пользователя',
            $sellerPackage->seller->user->balance < $sellerPackage->package->price => "недостаточно средств (баланс: {$sellerPackage->seller->user->balance} руб)",
            default => 'неизвестная причина',
        };
    }

    private function ensureEnoughBalance(User $user, Package $package): void
    {
        if ($package->price > 0 && $user->balance < $package->price) {
            throw new RuntimeException('Недостаточно средств на балансе');
        }
    }

    private function ensureSellerFitsPackage(Seller $seller, Package $package): void
    {
        $activeProductsCount = $this->productLimitService->activeProductsCount($seller);
        $maxProducts = $package->active_products_limit ?? PHP_INT_MAX;

        if ($maxProducts !== PHP_INT_MAX && $activeProductsCount > $maxProducts) {
            $excess = $activeProductsCount - $maxProducts;

            throw new RuntimeException("Тариф \"{$package->name}\" позволяет опубликовать не более {$maxProducts} товаров. У вас сейчас активно {$activeProductsCount} товаров. Пожалуйста, отключите {$excess} " . $this->productsWord($excess) . ', чтобы активировать этот пакет.');
        }

        $vipProductsCount = $this->productLimitService->vipProductsCount($seller);
        $maxVipProducts = $package->vip_products_limit ?? PHP_INT_MAX;

        if ($maxVipProducts !== PHP_INT_MAX && $vipProductsCount > $maxVipProducts) {
            $excessVip = $vipProductsCount - $maxVipProducts;

            if ($maxVipProducts === 0) {
                throw new RuntimeException("Тариф \"{$package->name}\" не поддерживает VIP товары. У вас сейчас {$vipProductsCount} VIP " . $this->productsWord($vipProductsCount) . '. Пожалуйста, снимите VIP-статус со всех товаров, чтобы активировать этот пакет.');
            }

            throw new RuntimeException("Тариф \"{$package->name}\" позволяет иметь не более {$maxVipProducts} VIP " . $this->productsWord($maxVipProducts) . ". У вас сейчас {$vipProductsCount} VIP " . $this->productsWord($vipProductsCount) . ". Пожалуйста, снимите VIP-статус с {$excessVip} " . $this->productsWord($excessVip) . ', чтобы активировать этот пакет.');
        }
    }

    private function chargePackage(User $user, Package $package): void
    {
        if ($package->price > 0) {
            $user->decrement('balance', $package->price);
        }
    }

    private function calculateEndDate(Package $package): ?\Illuminate\Support\Carbon
    {
        return $package->duration_days
            ? now()->addDays($package->duration_days)
            : null;
    }

    private function productsWord(int $count): string
    {
        return $count === 1 ? 'товар' : ($count < 5 ? 'товара' : 'товаров');
    }
}
