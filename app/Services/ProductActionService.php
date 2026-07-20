<?php

namespace App\Services;

use App\Exceptions\ProductActivationException;
use App\Exceptions\ProductImageDeletionException;
use App\Exceptions\ProductPushTooSoonException;
use App\Models\Image as ProductImage;
use App\Models\Product;
use Carbon\CarbonInterface;

class ProductActionService
{
    public function __construct(
        private readonly ProductLimitService $productLimitService,
        private readonly ProductImageService $productImageService,
        private readonly ProductModerationService $productModerationService,
        private readonly ProductStateTransitionService $transitions
    ) {}

    public function activate(Product $product): Product
    {
        if (! $this->transitions->canPublish($product)) {
            throw new ProductActivationException(
                'Товар нельзя опубликовать до завершения модерации актуальной версии.'
            );
        }

        $errorMessage = $this->productLimitService->activationError($product);

        if ($errorMessage !== null) {
            throw new ProductActivationException($errorMessage);
        }

        $product->update([
            'is_active' => true,
            'publication_requested' => true,
        ]);

        return $product->refresh();
    }

    public function deactivate(Product $product): Product
    {
        $product->update([
            'is_active' => false,
            'publication_requested' => false,
        ]);

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        $this->productImageService->deleteProductFiles($product);
        $product->delete();
    }

    public function deleteImage(ProductImage $image): Product
    {
        $product = $image->product;

        if ($product->is_active && $product->images()->count() === 1) {
            throw new ProductImageDeletionException(
                'Нельзя удалить последнее изображение активного товара. '
                    .'Сначала деактивируйте товар или загрузите новое фото.'
            );
        }

        $this->productImageService->deleteImage($image);

        return $this->productModerationService->invalidate($product);
    }

    public function setMainImage(ProductImage $image): void
    {
        $this->productImageService->setMainImage($image);
    }

    /**
     * @return array{product: Product, pushed_at: CarbonInterface, next_available_at: CarbonInterface}
     */
    public function pushUp(Product $product): array
    {
        if (! $this->transitions->canPushUp($product)) {
            throw new ProductPushTooSoonException(
                'Поднять можно только опубликованный товар.',
                now()
            );
        }

        $now = now();

        if ($product->push_at) {
            $availableAt = $product->push_at->copy()->addHours(24);

            if ($availableAt->isFuture()) {
                throw new ProductPushTooSoonException(
                    'Поднять товар можно только раз в 24 часа. Следующее поднятие доступно: '
                        .$availableAt->format('d.m.Y в H:i'),
                    $availableAt
                );
            }
        }

        $product->update(['push_at' => $now]);
        $product = $product->refresh();
        $pushedAt = $product->push_at;

        return [
            'product' => $product,
            'pushed_at' => $pushedAt,
            'next_available_at' => $pushedAt->copy()->addHours(24),
        ];
    }
}
