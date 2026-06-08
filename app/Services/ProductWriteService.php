<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ProductWriteService
{
    public function __construct(
        private readonly ProductLimitService $productLimitService,
        private readonly ProductImageService $productImageService,
        private readonly BackendProductService $backendProductService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function create(Seller $seller, array $data, array $images = []): Product
    {
        $category = $this->validateCategory($data['category_id']);
        unset($data['images']);
        $data = $this->normalizeFlags($data);

        $this->validateLimits(
            $this->productLimitService->validateForStore(
                $seller,
                $data['is_active'],
                $data['is_vip'],
                ! empty($images)
            ),
            $category
        );

        $data['alias'] = $this->backendProductService->generateUniqueAlias($data['name']);
        $data['seller_id'] = $seller->id;
        $data['push_at'] = now();
        $data['sale_started_at'] = ! empty($data['lowprice']) ? now() : null;

        $product = Product::create($data);

        if (! empty($images)) {
            $this->productImageService->storeUploadedImages($product, $images, true);
        }

        session()->forget('selectedPath');

        return $product;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function update(Product $product, array $data, array $images = []): Product
    {
        $category = $this->validateCategory($data['category_id']);
        unset($data['images']);
        $data = $this->normalizeFlags($data);

        $this->validateLimits(
            $this->productLimitService->validateForUpdate(
                $product,
                $data['is_active'],
                $data['is_vip'],
                $product->images()->exists() || ! empty($images)
            ),
            $category
        );

        if (! empty($images) && ! $this->productImageService->canAddImages($product, count($images))) {
            $this->fail([
                'images' => 'Превышен лимит изображений. Можно загрузить ещё '
                    .$this->productImageService->remainingImageSlots($product)
                    .' фото.',
            ], $category);
        }

        if (! empty($data['lowprice']) && ! $product->lowprice) {
            $data['sale_started_at'] = now();
        } elseif (empty($data['lowprice'])) {
            $data['sale_started_at'] = null;
        }

        $product->update($data);

        if (! empty($images)) {
            $this->productImageService->storeUploadedImages(
                $product,
                $images,
                ! $product->images()->where('is_main', true)->exists()
            );
        }

        session()->forget('selectedPath');

        return $product->refresh();
    }

    private function validateCategory(int $categoryId): Category
    {
        $category = Category::findOrFail($categoryId);

        if (! $category->isLeaf()) {
            $this->fail([
                'category_id' => 'Пожалуйста, выберите подкатегорию последнего уровня для размещения товара. '
                    .'Нельзя добавлять товары в категории, у которых есть подкатегории.',
            ], $category);
        }

        return $category;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeFlags(array $data): array
    {
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['is_vip'] = (bool) ($data['is_vip'] ?? false);

        return $data;
    }

    /**
     * @param  array<string, string>  $errors
     */
    private function validateLimits(array $errors, Category $category): void
    {
        if (! empty($errors)) {
            $this->fail($errors, $category);
        }
    }

    /**
     * @param  array<string, string>  $errors
     */
    private function fail(array $errors, Category $category): never
    {
        $this->backendProductService->rememberSelectedPath($category);

        throw ValidationException::withMessages($errors);
    }
}
