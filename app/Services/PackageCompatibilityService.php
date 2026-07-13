<?php

namespace App\Services;

use App\Data\PackageCompatibility;
use App\Models\Package;
use App\Models\Seller;
use App\Models\User;

class PackageCompatibilityService
{
    public function make(User $user, ?Seller $seller, Package $package): PackageCompatibility
    {
        $currentPackage = $seller?->activePackage();
        $isCurrent = $currentPackage !== null && (int) $currentPackage->package_id === (int) $package->id;
        $balance = $user->balance ?? 0;
        $activeProductsCount = $seller?->products()->where('is_active', true)->count() ?? 0;
        $vipProductsCount = $seller?->products()
            ->where('is_active', true)
            ->where('is_vip', true)
            ->count() ?? 0;

        $activeProductsLimit = $package->active_products_limit;
        $vipProductsLimit = $package->vip_products_limit;
        $activeProductsFit = $activeProductsLimit === null || $activeProductsCount <= $activeProductsLimit;
        $vipProductsFit = $vipProductsLimit === null || $vipProductsCount <= $vipProductsLimit;
        $hasEnoughBalance = (float) $balance >= (float) $package->price;
        $willDisableWebsite = $seller !== null && filled($seller->link) && ! $package->has_website_link;
        $willDisableSocialLinks = $seller !== null
            && (filled($seller->telegram) || filled($seller->instagram) || filled($seller->viber))
            && ! $package->has_social_links;
        $willDisableOrders = $seller !== null && (bool) $seller->can_order && ! $package->has_order_system;
        $requiredActiveDeactivations = $activeProductsFit || $activeProductsLimit === null
            ? 0
            : $activeProductsCount - $activeProductsLimit;
        $requiredVipDeactivations = $vipProductsFit || $vipProductsLimit === null
            ? 0
            : $vipProductsCount - $vipProductsLimit;

        $reasons = [];
        $warnings = [];

        if (! $hasEnoughBalance) {
            $reasons[] = 'Недостаточно средств на балансе.';
        }

        if (! $activeProductsFit) {
            $reasons[] = "Активных товаров {$activeProductsCount}, тариф разрешает {$activeProductsLimit}. Нужно снять с публикации {$requiredActiveDeactivations}.";
        }

        if (! $vipProductsFit) {
            $reasons[] = "VIP-товаров {$vipProductsCount}, тариф разрешает {$vipProductsLimit}. Нужно снять VIP с {$requiredVipDeactivations}.";
        }

        if ($willDisableWebsite) {
            $warnings[] = 'Сайт сохранится, но не будет показываться покупателям на этом тарифе.';
        }

        if ($willDisableSocialLinks) {
            $warnings[] = 'Соцсети сохранятся, но не будут показываться покупателям на этом тарифе.';
        }

        if ($willDisableOrders) {
            $warnings[] = 'Новые заказы через сайт будут недоступны на этом тарифе.';
        }

        [$status, $action, $actionLabel, $canPurchaseNow] = $this->resolveStatus(
            isCurrent: $isCurrent,
            hasEnoughBalance: $hasEnoughBalance,
            activeProductsFit: $activeProductsFit,
            vipProductsFit: $vipProductsFit,
            hasFeatureWarnings: $warnings !== [],
        );

        return new PackageCompatibility(
            status: $status,
            isCurrent: $isCurrent,
            canPurchaseNow: $canPurchaseNow,
            hasEnoughBalance: $hasEnoughBalance,
            activeProductsFit: $activeProductsFit,
            vipProductsFit: $vipProductsFit,
            willDisableWebsite: $willDisableWebsite,
            willDisableSocialLinks: $willDisableSocialLinks,
            willDisableOrders: $willDisableOrders,
            activeProductsCount: $activeProductsCount,
            vipProductsCount: $vipProductsCount,
            activeProductsLimit: $activeProductsLimit,
            vipProductsLimit: $vipProductsLimit,
            balance: $balance,
            price: $package->price,
            requiredActiveDeactivations: $requiredActiveDeactivations,
            requiredVipDeactivations: $requiredVipDeactivations,
            reasons: $reasons,
            warnings: $warnings,
            action: $action,
            actionLabel: $actionLabel,
        );
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: bool}
     */
    private function resolveStatus(
        bool $isCurrent,
        bool $hasEnoughBalance,
        bool $activeProductsFit,
        bool $vipProductsFit,
        bool $hasFeatureWarnings,
    ): array {
        if ($isCurrent) {
            return ['current', 'none', 'Текущий тариф', false];
        }

        if (! $hasEnoughBalance) {
            return ['insufficient_balance', 'top_up_balance', 'Недостаточно средств', false];
        }

        if (! $activeProductsFit || ! $vipProductsFit) {
            return ['needs_preparation', 'prepare_transition', 'Подготовить переход', false];
        }

        if ($hasFeatureWarnings) {
            return ['feature_downgrade', 'purchase_with_warning', 'Подключить с ограничениями', true];
        }

        return ['available', 'purchase', 'Подключить', true];
    }
}
