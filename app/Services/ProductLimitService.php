<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Seller;

class ProductLimitService
{
    private const STORE_PRODUCT_WITHOUT_IMAGES_MESSAGE = 'Нельзя активировать товар без изображений. Загрузите хотя бы одно фото или поставьте статус "Снят с публикации".';
    private const UPDATE_PRODUCT_WITHOUT_IMAGES_MESSAGE = 'Нельзя активировать товар без изображений. Загрузите хотя бы одно фото.';

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
        if (!$product->images()->exists()) {
            return self::UPDATE_PRODUCT_WITHOUT_IMAGES_MESSAGE;
        }

        $errors = $this->validateForUpdate($product, true, (bool) $product->is_vip, $product->images()->exists());

        return $errors['is_active'] ?? $errors['is_vip'] ?? null;
    }

    private function validate(
        Seller $seller,
        bool $isActive,
        bool $isVip,
        ?int $excludeProductId,
        bool $hasImages,
        string $missingImagesMessage
    ): array {
        if (!$isActive) {
            return [];
        }

        $errors = [];

        if (!$hasImages) {
            $errors['is_active'] = $missingImagesMessage;
        }

        $activeCheck = $seller->canActivateProduct($excludeProductId);
        if (!$activeCheck['allowed']) {
            $errors['is_active'] = $activeCheck['message'];
        }

        if ($isVip) {
            $vipCheck = $seller->canActivateVipProduct($excludeProductId);
            if (!$vipCheck['allowed']) {
                $errors['is_vip'] = $vipCheck['message'];
            }
        }

        return $errors;
    }
}
