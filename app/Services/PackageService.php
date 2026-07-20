<?php

namespace App\Services;

use App\Data\PackageCompatibility;
use App\Models\Package;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PackageService
{
    public function __construct(private readonly PackageCompatibilityService $packageCompatibilityService) {}

    public function purchase(User $user, Seller $seller, Package $package): SellerPackage
    {
        return $this->activate($user, $seller, $package, chargeBalance: true);
    }

    public function assignWithoutCharge(User $user, Seller $seller, Package $package): SellerPackage
    {
        return $this->activate($user, $seller, $package, chargeBalance: false);
    }

    public function renew(SellerPackage $sellerPackage): SellerPackage
    {
        return $this->renewSubscription($sellerPackage, chargeBalance: true);
    }

    public function renewWithoutCharge(SellerPackage $sellerPackage): SellerPackage
    {
        return $this->renewSubscription($sellerPackage, chargeBalance: false);
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
            ! $autoRenewEnabled => 'автопродление не включено в команде',
            ! $sellerPackage->seller->auto_renew => 'продавец отключил автопродление',
            ! $sellerPackage->seller->user => 'у продавца нет пользователя',
            $sellerPackage->seller->user->balance < $sellerPackage->package->price => "недостаточно средств (баланс: {$sellerPackage->seller->user->balance} руб)",
            default => 'неизвестная причина',
        };
    }

    private function activate(
        User $user,
        Seller $seller,
        Package $package,
        bool $chargeBalance
    ): SellerPackage {
        return DB::transaction(function () use ($user, $seller, $package, $chargeBalance): SellerPackage {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedSeller = Seller::query()->lockForUpdate()->findOrFail($seller->id);
            $lockedPackage = Package::query()->lockForUpdate()->findOrFail($package->id);

            $this->ensurePackageIsActive($lockedPackage);

            $compatibility = $this->packageCompatibilityService->make($lockedUser, $lockedSeller, $lockedPackage);
            $this->ensurePurchaseConditions($lockedPackage, $compatibility, $lockedUser, $chargeBalance);

            $lockedSeller->sellerPackages()
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $sellerPackage = $lockedSeller->sellerPackages()->create([
                'package_id' => $lockedPackage->id,
                'start_date' => now(),
                'end_date' => $this->calculateEndDate($lockedPackage),
                'is_active' => true,
            ]);

            if ($chargeBalance) {
                $this->chargePackage($lockedUser, $lockedPackage);
            }

            return $sellerPackage;
        });
    }

    private function renewSubscription(SellerPackage $sellerPackage, bool $chargeBalance): SellerPackage
    {
        return DB::transaction(function () use ($sellerPackage, $chargeBalance): SellerPackage {
            $lockedSellerPackage = SellerPackage::query()
                ->with(['seller.user', 'package'])
                ->lockForUpdate()
                ->findOrFail($sellerPackage->id);

            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($lockedSellerPackage->seller->user_id);
            $package = $lockedSellerPackage->package;

            $this->ensurePackageIsActive($package);

            if ($chargeBalance) {
                $this->ensureEnoughBalance($user, $package);
                $this->chargePackage($user, $package);
            }

            $lockedSellerPackage->seller
                ->sellerPackages()
                ->whereKeyNot($lockedSellerPackage->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $lockedSellerPackage->update([
                'start_date' => now(),
                'end_date' => $this->calculateEndDate($package),
                'is_active' => true,
            ]);

            return $lockedSellerPackage->refresh();
        });
    }

    private function ensurePackageIsActive(Package $package): void
    {
        if (! $package->is_active) {
            throw new RuntimeException('Нельзя активировать отключённый тариф');
        }
    }

    private function ensurePurchaseConditions(
        Package $package,
        PackageCompatibility $compatibility,
        User $user,
        bool $chargeBalance
    ): void {
        if ($chargeBalance && ! $compatibility->hasEnoughBalance) {
            $this->ensureEnoughBalance($user, $package);
        }

        if (! $compatibility->activeProductsFit) {
            $excess = $compatibility->requiredActiveDeactivations;

            throw new RuntimeException("Тариф \"{$package->name}\" позволяет публиковать не более {$compatibility->activeProductsLimit} товаров. У вас сейчас активно {$compatibility->activeProductsCount} товаров. Пожалуйста, отключите {$excess} " . $this->productsWord($excess) . ', чтобы активировать этот пакет.');
        }

        if (! $compatibility->vipProductsFit) {
            $maxVipProducts = $compatibility->vipProductsLimit ?? PHP_INT_MAX;
            $excessVip = $compatibility->requiredVipDeactivations;

            if ($maxVipProducts === 0) {
                throw new RuntimeException("Тариф \"{$package->name}\" не поддерживает VIP товары. У вас сейчас {$compatibility->vipProductsCount} VIP " . $this->productsWord($compatibility->vipProductsCount) . '. Пожалуйста, снимите VIP-статус со всех товаров, чтобы активировать этот пакет.');
            }

            throw new RuntimeException("Тариф \"{$package->name}\" позволяет иметь не более {$maxVipProducts} VIP " . $this->productsWord($maxVipProducts) . ". У вас сейчас {$compatibility->vipProductsCount} VIP " . $this->productsWord($compatibility->vipProductsCount) . ". Пожалуйста, снимите VIP-статус с {$excessVip} " . $this->productsWord($excessVip) . ', чтобы активировать этот пакет.');
        }
    }

    private function ensureEnoughBalance(User $user, Package $package): void
    {
        if ($package->price > 0 && $user->balance < $package->price) {
            throw new RuntimeException('Недостаточно средств на балансе');
        }
    }

    private function chargePackage(User $user, Package $package): void
    {
        if ($package->price > 0) {
            $user->decrement('balance', $package->price);
        }
    }

    private function calculateEndDate(Package $package): ?Carbon
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
