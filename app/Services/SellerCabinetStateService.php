<?php

namespace App\Services;

use App\Data\SellerCabinetState;
use App\Enums\ProductModerationStatus;
use App\Models\Product;
use App\Models\Seller;

class SellerCabinetStateService
{
    public function make(?Seller $seller): SellerCabinetState
    {
        if ($seller === null) {
            return new SellerCabinetState(
                status: 'no_store',
                label: 'Магазин ещё не создан',
                description: 'Создайте магазин, чтобы добавлять товары и принимать заказы.',
                alertType: 'info',
                primaryAction: 'create_store',
                primaryActionLabel: 'Создать магазин',
                blockers: [],
                hasActivePackage: false,
                packageName: null,
                packageEndsAt: null,
                packageDaysLeft: null,
                packageIsLifetime: false,
                packageRenewalPrice: null,
                autoRenewEnabled: false,
                autoRenewPossible: false,
                balance: 0,
                activeProductsCount: 0,
                visibleProductsCount: 0,
                activeProductsLimit: null,
                vipProductsCount: 0,
                vipProductsLimit: null,
                canShowWebsite: false,
                canShowSocialLinks: false,
                canAcceptOrders: false,
            );
        }

        $seller->loadMissing('user');
        $activePackage = $seller->activePackage();
        $package = $activePackage?->package;
        $balance = $seller->user?->balance ?? 0;
        $packageDaysLeft = $activePackage?->end_date !== null
            ? (int) now()->startOfDay()->diffInDays($activePackage->end_date->copy()->startOfDay(), false)
            : null;
        $activeProductsCount = $seller->products()->where('is_active', true)->count();
        $visibleProductsCount = Product::query()
            ->visible()
            ->where('seller_id', $seller->id)
            ->count();
        $vipProductsCount = $seller->products()
            ->where('is_active', true)
            ->where('is_vip', true)
            ->count();

        $canShowWebsite = (bool) ($package?->has_website_link ?? false);
        $canShowSocialLinks = (bool) ($package?->has_social_links ?? false);
        $canAcceptOrdersByPackage = (bool) ($package?->has_order_system ?? false);
        $canAcceptOrders = $canAcceptOrdersByPackage && (bool) $seller->can_order;
        $autoRenewPossible = $activePackage !== null
            && $package !== null
            && (float) $balance >= (float) $package->price;

        $blockers = $this->blockers(
            seller: $seller,
            hasActivePackage: $activePackage !== null,
            activeProductsCount: $activeProductsCount,
            visibleProductsCount: $visibleProductsCount,
            activeProductsLimit: $package?->active_products_limit,
            vipProductsCount: $vipProductsCount,
            vipProductsLimit: $package?->vip_products_limit,
            canShowWebsite: $canShowWebsite,
            canShowSocialLinks: $canShowSocialLinks,
            canAcceptOrdersByPackage: $canAcceptOrdersByPackage,
            autoRenewPossible: $autoRenewPossible,
            packageDaysLeft: $packageDaysLeft,
        );

        [$status, $label, $description, $alertType] = $this->status(
            hasActivePackage: $activePackage !== null,
            visibleProductsCount: $visibleProductsCount,
            blockers: $blockers,
            packageDaysLeft: $packageDaysLeft,
        );
        $primaryBlocker = $blockers[0] ?? null;

        return new SellerCabinetState(
            status: $status,
            label: $label,
            description: $description,
            alertType: $alertType,
            primaryAction: $primaryBlocker['action'] ?? $this->defaultAction($status),
            primaryActionLabel: $this->actionLabel($primaryBlocker['action'] ?? $this->defaultAction($status)),
            blockers: $blockers,
            hasActivePackage: $activePackage !== null,
            packageName: $package?->name,
            packageEndsAt: $activePackage?->end_date,
            packageDaysLeft: $packageDaysLeft,
            packageIsLifetime: $activePackage !== null && $activePackage->end_date === null,
            packageRenewalPrice: $package?->price,
            autoRenewEnabled: (bool) $seller->auto_renew,
            autoRenewPossible: $autoRenewPossible,
            balance: $balance,
            activeProductsCount: $activeProductsCount,
            visibleProductsCount: $visibleProductsCount,
            activeProductsLimit: $package?->active_products_limit,
            vipProductsCount: $vipProductsCount,
            vipProductsLimit: $package?->vip_products_limit,
            canShowWebsite: $canShowWebsite,
            canShowSocialLinks: $canShowSocialLinks,
            canAcceptOrders: $canAcceptOrders,
        );
    }

    private function blockers(
        Seller $seller,
        bool $hasActivePackage,
        int $activeProductsCount,
        int $visibleProductsCount,
        ?int $activeProductsLimit,
        int $vipProductsCount,
        ?int $vipProductsLimit,
        bool $canShowWebsite,
        bool $canShowSocialLinks,
        bool $canAcceptOrdersByPackage,
        bool $autoRenewPossible,
        ?int $packageDaysLeft,
    ): array {
        $blockers = [];
        $activePackage = $seller->activePackage();

        if (! $hasActivePackage) {
            $blockers[] = $this->blocker(
                'no_active_package',
                'Товары скрыты от покупателей, потому что нет активного тарифа.',
                'choose_package'
            );
        }

        if (
            $hasActivePackage
            && $activePackage?->end_date !== null
            && $packageDaysLeft !== null
            && $packageDaysLeft >= 0
            && $packageDaysLeft <= 7
            && $seller->auto_renew
            && ! $autoRenewPossible
        ) {
            $blockers[] = $this->blocker(
                'auto_renew_insufficient_balance',
                'Тариф скоро закончится, но баланса не хватает для автопродления.',
                'top_up_balance'
            );
        }

        if ($activeProductsLimit !== null && $activeProductsCount > $activeProductsLimit) {
            $blockers[] = $this->blocker(
                'active_limit_exceeded',
                'Активных товаров больше, чем разрешает текущий тариф.',
                'choose_visible_products'
            );
        }

        if ($vipProductsLimit !== null && $vipProductsCount > $vipProductsLimit) {
            $blockers[] = $this->blocker(
                'vip_limit_exceeded',
                'VIP-товаров больше, чем разрешает текущий тариф.',
                'remove_extra_vip'
            );
        }

        if ($hasActivePackage && $visibleProductsCount === 0) {
            $blockers[] = $this->blocker(
                'no_visible_products',
                'Покупатели пока не видят товары магазина.',
                'open_products'
            );
        }

        if ($this->productsInModerationCount($seller) > 0) {
            $blockers[] = $this->blocker(
                'products_need_moderation',
                'Часть товаров ждёт проверки.',
                'open_products'
            );
        }

        if ($this->productsNeedFixesCount($seller) > 0) {
            $blockers[] = $this->blocker(
                'products_need_fixes',
                'В части товаров нужно исправить замечания.',
                'open_products'
            );
        }

        if ($seller->products()->whereDoesntHave('images')->count() > 0) {
            $blockers[] = $this->blocker(
                'products_without_images',
                'Некоторые товары нельзя опубликовать без фото.',
                'open_products'
            );
        }

        if (filled($seller->link) && ! $canShowWebsite) {
            $blockers[] = $this->blocker(
                'website_hidden_by_package',
                'Сайт сохранён, но не показывается на текущем тарифе.',
                'choose_package'
            );
        }

        if ((filled($seller->telegram) || filled($seller->instagram) || filled($seller->viber)) && ! $canShowSocialLinks) {
            $blockers[] = $this->blocker(
                'social_links_hidden_by_package',
                'Соцсети сохранены, но не показываются на текущем тарифе.',
                'choose_package'
            );
        }

        if ($seller->can_order && ! $canAcceptOrdersByPackage) {
            $blockers[] = $this->blocker(
                'orders_disabled_by_package',
                'Новые заказы через сайт недоступны на текущем тарифе.',
                'choose_package'
            );
        } elseif ($hasActivePackage && $canAcceptOrdersByPackage && ! $seller->can_order) {
            $blockers[] = $this->blocker(
                'orders_disabled_by_seller',
                'Приём заказов выключен в настройках магазина.',
                'edit_store'
            );
        }

        return $blockers;
    }

    private function status(
        bool $hasActivePackage,
        int $visibleProductsCount,
        array $blockers,
        ?int $packageDaysLeft,
    ): array {
        if (! $hasActivePackage) {
            return [
                'hidden_no_package',
                'Магазин скрыт: нет активного тарифа',
                'Выберите тариф, чтобы товары снова появились в каталоге.',
                'danger',
            ];
        }

        if ($visibleProductsCount === 0) {
            return [
                'empty',
                'Магазин создан, но покупатели пока не видят товары',
                'Добавьте товар или исправьте причины скрытия.',
                'warning',
            ];
        }

        if ($blockers !== []) {
            return [
                'needs_attention',
                'Магазину нужно внимание',
                'Есть настройки или товары, которые требуют проверки.',
                'warning',
            ];
        }

        if ($packageDaysLeft !== null && $packageDaysLeft >= 0 && $packageDaysLeft <= 7) {
            return [
                'package_expiring',
                'Тариф скоро закончится',
                'Проверьте автопродление и баланс.',
                'warning',
            ];
        }

        return [
            'working',
            'Магазин работает',
            'Товары магазина видны покупателям.',
            'success',
        ];
    }

    private function productsInModerationCount(Seller $seller): int
    {
        return $seller->products()
            ->whereIn('moderation_status', [
                ProductModerationStatus::Pending->value,
                ProductModerationStatus::Processing->value,
                ProductModerationStatus::NeedsReview->value,
                ProductModerationStatus::Failed->value,
            ])
            ->count();
    }

    private function productsNeedFixesCount(Seller $seller): int
    {
        return $seller->products()
            ->whereIn('moderation_status', [
                ProductModerationStatus::ChangesRequired->value,
                ProductModerationStatus::Rejected->value,
            ])
            ->count();
    }

    private function blocker(string $code, string $message, ?string $action): array
    {
        return compact('code', 'message', 'action');
    }

    private function defaultAction(string $status): ?string
    {
        return match ($status) {
            'no_store' => 'create_store',
            'hidden_no_package' => 'choose_package',
            'empty' => 'add_product',
            'working' => 'open_store',
            'package_expiring' => 'choose_package',
            default => null,
        };
    }

    private function actionLabel(?string $action): ?string
    {
        return match ($action) {
            'create_store' => 'Создать магазин',
            'choose_package' => 'Выбрать тариф',
            'top_up_balance' => 'Пополнить баланс',
            'choose_visible_products' => 'Выбрать товары для показа',
            'remove_extra_vip' => 'Снять лишние VIP',
            'open_products' => 'Открыть товары',
            'edit_store' => 'Открыть настройки',
            'add_product' => 'Добавить товар',
            'open_store' => 'Открыть магазин',
            default => null,
        };
    }
}
