<?php

namespace App\Services;

use App\Models\Product;

/**
 * Проверки перед публикацией товара.
 *
 * Лимитов на количество товаров нет: единственное условие публикации,
 * которое проверяется здесь, — наличие хотя бы одного изображения.
 */
class ProductLimitService
{
    private const PRODUCT_WITHOUT_IMAGES_MESSAGE = 'Добавьте хотя бы одно фото: без фото товар нельзя отправить на проверку и опубликовать. Пока фото нет, нажмите «Сохранить черновик».';

    public function validateForStore(bool $isActive, bool $hasImages): array
    {
        return $this->validate($isActive, $hasImages);
    }

    public function validateForUpdate(bool $isActive, bool $hasImages): array
    {
        return $this->validate($isActive, $hasImages);
    }

    public function activationError(Product $product): ?string
    {
        if (! $product->images()->exists()) {
            return self::PRODUCT_WITHOUT_IMAGES_MESSAGE;
        }

        return null;
    }

    private function validate(bool $isActive, bool $hasImages): array
    {
        if (! $isActive) {
            return [];
        }

        if (! $hasImages) {
            return ['is_active' => self::PRODUCT_WITHOUT_IMAGES_MESSAGE];
        }

        return [];
    }
}
