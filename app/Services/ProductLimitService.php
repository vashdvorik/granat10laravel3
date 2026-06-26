<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;

class ProductLimitService
{
    private const STORE_PRODUCT_WITHOUT_IMAGES_MESSAGE = 'Нельзя опубликовать товар без изображений. Загрузите хотя бы одно фото или нажмите "Сохранить черновик".';

    private const UPDATE_PRODUCT_WITHOUT_IMAGES_MESSAGE = 'Нельзя опубликовать товар без изображений. Загрузите хотя бы одно фото или нажмите "Сохранить черновик".';

    public function validateForStore(Seller $seller, bool $isActive, bool $isVip, bool $hasImages): array
    {
        return $this->validate($seller, $isActive, $isVip, null, $hasImages, self::STORE_PRODUCT_WITHOUT_IMAGES_MESSAGE);
    }

    public function validateForUpdate(Product $product, bool $isActive, bool $isVip, bool $hasImages): array
    {
        return $this->validate($product->seller, $isActive, $isVip, $product->id, $hasImages, self::UPDATE_PRODUCT_WITHOUT_IMAGES_MESSAGE);
    }

    public function activationError(Product $product): ?string
    {
        if (! $product->images()->exists()) {
            return self::UPDATE_PRODUCT_WITHOUT_IMAGES_MESSAGE;
        }

        $errors = $this->validateForUpdate($product, true, (bool) $product->is_vip, $product->images()->exists());

        return $errors['is_active'] ?? $errors['is_vip'] ?? null;
    }

    public function activeProductsCount(Seller $seller): int
    {
        return $seller->products()->where('is_active', true)->count();
    }

    public function vipProductsCount(Seller $seller): int
    {
        return $seller->products()
            ->where('is_active', true)
            ->where('is_vip', true)
            ->count();
    }

    public function canActivateProduct(Seller $seller, ?int $excludeProductId = null): array
    {
        return $this->checkLimit(
            $seller,
            'active_products_limit',
            'Можно активировать товар',
            'Достигнут лимит активных товаров',
            $excludeProductId
        );
    }

    public function canActivateVipProduct(Seller $seller, ?int $excludeProductId = null): array
    {
        return $this->checkLimit(
            $seller,
            'vip_products_limit',
            'Можно активировать VIP товар',
            'Достигнут лимит VIP товаров',
            $excludeProductId,
            vipOnly: true
        );
    }

    public function checkProductLimits(
        Seller $seller,
        bool $isActive,
        bool $isVip,
        ?int $excludeProductId = null
    ): array {
        if (! $isActive) {
            return ['allowed' => true, 'errors' => []];
        }

        $errors = [];
        $activeCheck = $this->canActivateProduct($seller, $excludeProductId);

        if (! $activeCheck['allowed']) {
            $errors[] = $activeCheck['message'];
        }

        if ($isVip) {
            $vipCheck = $this->canActivateVipProduct($seller, $excludeProductId);

            if (! $vipCheck['allowed']) {
                $errors[] = $vipCheck['message'];
            }
        }

        return [
            'allowed' => $errors === [],
            'errors' => $errors,
        ];
    }

    private function validate(
        Seller $seller,
        bool $isActive,
        bool $isVip,
        ?int $excludeProductId,
        bool $hasImages,
        string $missingImagesMessage
    ): array {
        if (! $isActive) {
            return [];
        }

        $errors = [];

        if (! $hasImages) {
            $errors['is_active'] = $missingImagesMessage;
        }

        $activeCheck = $this->canActivateProduct($seller, $excludeProductId);
        if (! $activeCheck['allowed']) {
            $errors['is_active'] = $activeCheck['message'];
        }

        if ($isVip) {
            $vipCheck = $this->canActivateVipProduct($seller, $excludeProductId);
            if (! $vipCheck['allowed']) {
                $errors['is_vip'] = $vipCheck['message'];
            }
        }

        return $errors;
    }

    private function checkLimit(
        Seller $seller,
        string $limitField,
        string $allowedMessage,
        string $limitMessage,
        ?int $excludeProductId,
        bool $vipOnly = false
    ): array {
        $activePackage = $seller->activePackage();

        if (! $activePackage instanceof SellerPackage) {
            return [
                'allowed' => false,
                'message' => 'У вас нет активного тарифного пакета',
                'current' => 0,
                'limit' => 0,
            ];
        }

        $package = $activePackage->package;
        $limit = $package->{$limitField};
        $query = $seller->products()->where('is_active', true);

        if ($vipOnly) {
            $query->where('is_vip', true);
        }

        if ($excludeProductId) {
            $query->where('id', '!=', $excludeProductId);
        }

        $current = $query->count();
        $allowed = $limit === null || $current < $limit;
        $blockedMessage = $vipOnly
            ? "Ваш лимит превышен {$current}/{$limit}, пожалуйста выберите другой тариф или выключите другие VIP товары."
            : "{$limitMessage} ({$current}/{$limit}). Обновите тариф.";

        return [
            'allowed' => $allowed,
            'message' => $allowed
                ? $allowedMessage
                : $blockedMessage,
            'current' => $current,
            'limit' => $limit,
            'package_name' => $package->name,
        ];
    }
}
