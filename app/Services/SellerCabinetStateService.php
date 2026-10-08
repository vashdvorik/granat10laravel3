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
                balance: 0,
                activeProductsCount: 0,
                visibleProductsCount: 0,
                vipProductsCount: 0,
                canAcceptOrders: false,
            );
        }

        $seller->loadMissing('user');
        $balance = $seller->user?->balance ?? 0;
        $activeProductsCount = $seller->products()->where('is_active', true)->count();
        $visibleProductsCount = Product::query()
            ->visible()
            ->where('seller_id', $seller->id)
            ->count();
        $vipProductsCount = $seller->products()
            ->where('is_active', true)
            ->vipActive()
            ->count();
        $canAcceptOrders = (bool) $seller->can_order;

        $blockers = $this->blockers(
            seller: $seller,
            visibleProductsCount: $visibleProductsCount,
        );

        [$status, $label, $description, $alertType] = $this->status(
            visibleProductsCount: $visibleProductsCount,
            blockers: $blockers,
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
            balance: $balance,
            activeProductsCount: $activeProductsCount,
            visibleProductsCount: $visibleProductsCount,
            vipProductsCount: $vipProductsCount,
            canAcceptOrders: $canAcceptOrders,
        );
    }

    private function blockers(Seller $seller, int $visibleProductsCount): array
    {
        $blockers = [];

        if ($visibleProductsCount === 0) {
            $blockers[] = $this->blocker(
                'no_visible_products',
                'Покупатели пока не видят товары магазина.',
                'open_products'
            );
        }

        if ($this->productsInModerationCount($seller) > 0) {
            $blockers[] = $this->blocker(
                'products_need_moderation',
                'Некоторые товары находяться на модерации.',
                'open_products'
            );
        }

        if ($this->productsNeedFixesCount($seller) > 0) {
            $blockers[] = $this->blocker(
                'products_need_fixes',
                'Некоторые товары требуют исправлений, чтобы их опубликовать.',
                'open_products'
            );
        }

        if ($seller->products()->whereDoesntHave('images')->count() > 0) {
            $blockers[] = $this->blocker(
                'products_without_images',
                'Некоторые товары не имеют изображений, поэтому не показываются покупателям.',
                'open_products'
            );
        }

        if (! $seller->can_order) {
            $blockers[] = $this->blocker(
                'orders_disabled_by_seller',
                'Приём заказов выключен продавцом в настройках магазина.',
                'edit_store'
            );
        }

        return $blockers;
    }

    private function status(int $visibleProductsCount, array $blockers): array
    {
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
            'empty' => 'add_product',
            'working' => 'open_store',
            default => null,
        };
    }

    private function actionLabel(?string $action): ?string
    {
        return match ($action) {
            'create_store' => 'Создать магазин',
            'open_products' => 'Открыть товары',
            'edit_store' => 'Открыть настройки',
            'add_product' => 'Добавить товар',
            'open_store' => 'Открыть магазин',
            default => null,
        };
    }
}
