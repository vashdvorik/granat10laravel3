<?php

namespace App\Services;

use App\Enums\ProductModerationStatus;
use App\Enums\ProductSaveAction;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ProductWriteService
{
    public function __construct(
        private readonly ProductLimitService $productLimitService,
        private readonly ProductImageService $productImageService,
        private readonly BackendProductService $backendProductService,
        private readonly ProductModerationService $productModerationService,
        private readonly ProductLocalModerationAnalyzer $localModerationAnalyzer
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function create(
        Seller $seller,
        array $data,
        array $images = [],
        ?User $actor = null
    ): Product {
        $category = $this->validateCategory($data['category_id']);
        unset($data['images']);
        $saveAction = $this->saveAction($data);
        unset($data['save_action']);
        $data = $this->normalizeFlags($data);
        $submitForModeration = $saveAction->requestsModeration();
        $data['publication_requested'] = $submitForModeration;

        if ($submitForModeration) {
            $this->validateLocalModeration($data, $category);
        }

        $this->validateLimits(
            $this->productLimitService->validateForStore(
                $seller,
                $submitForModeration,
                $data['is_vip'],
                ! empty($images)
            ),
            $category
        );

        $data['alias'] = $this->backendProductService->generateUniqueAlias($data['name']);
        $data['seller_id'] = $seller->id;
        $data['push_at'] = now();
        $data['sale_started_at'] = ! empty($data['lowprice']) ? now() : null;
        $data['is_active'] = false;

        $product = new Product($data);
        $product->forceFill([
            'moderation_status' => ProductModerationStatus::Draft,
            'moderation_version' => 1,
            'moderated_version' => null,
            'moderation_source' => null,
            'moderation_submitted_at' => null,
            'moderated_at' => null,
        ]);
        $product->save();

        if (! empty($images)) {
            $this->productImageService->storeUploadedImages($product, $images, true);
        }

        if ($submitForModeration) {
            if ($actor === null) {
                throw new \LogicException('Для отправки товара на модерацию требуется пользователь.');
            }

            $product = $this->productModerationService->submit($product, $actor);
        }

        return $product;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function update(
        Product $product,
        array $data,
        array $images = [],
        ?User $actor = null
    ): Product {
        $expectedModerationVersion = isset($data['expected_moderation_version'])
            ? (int) $data['expected_moderation_version']
            : null;
        unset($data['expected_moderation_version']);

        $product->refresh();

        if (
            $expectedModerationVersion !== null
            && $product->moderation_version !== $expectedModerationVersion
        ) {
            throw ValidationException::withMessages([
                'product' => 'Товар изменился в другой вкладке. Обновите страницу и проверьте изменения.',
            ]);
        }

        $category = $this->validateCategory($data['category_id']);
        unset($data['images']);
        $saveAction = $this->saveAction($data);
        unset($data['save_action']);
        $data = $this->normalizeFlags($data);
        $submitForModeration = $saveAction->requestsModeration();
        $data['publication_requested'] = $submitForModeration;

        $this->validateLimits(
            $this->productLimitService->validateForUpdate(
                $product,
                $submitForModeration,
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

        $moderatedContentChanged = $this->moderatedContentChanged($product, $data)
            || ! empty($images);

        if ($saveAction === ProductSaveAction::Save && $moderatedContentChanged) {
            $this->fail([
                'save_action' => 'Эти изменения требуют повторной проверки. '
                    .'Выберите сохранение черновика или отправку на проверку.',
            ], $category);
        }

        if ($moderatedContentChanged) {
            $data['is_active'] = false;
        } else {
            unset($data['publication_requested']);
        }

        $product->update($data);

        if (! empty($images)) {
            $this->productImageService->storeUploadedImages(
                $product,
                $images,
                ! $product->images()->where('is_main', true)->exists()
            );
        }

        if ($moderatedContentChanged) {
            $product = $this->productModerationService->invalidate($product);

            if ($submitForModeration) {
                if ($actor === null) {
                    throw new \LogicException('Для отправки товара на модерацию требуется пользователь.');
                }

                $product = $this->productModerationService->submit($product, $actor);
            }
        }

        return $product->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function moderatedContentChanged(Product $product, array $data): bool
    {
        $fields = [
            'name',
            'desc',
            'features1',
            'features2',
            'features3',
            'features4',
            'category_id',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data) && $product->getAttribute($field) != $data[$field]) {
                return true;
            }
        }

        return false;
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
        $data['is_vip'] = (bool) ($data['is_vip'] ?? false);
        $data['can_order'] = (bool) ($data['can_order'] ?? false);
        $data['delivery_enabled'] = $data['can_order']
            && (bool) ($data['delivery_enabled'] ?? false);

        return $data;
    }

    private function saveAction(array $data): ProductSaveAction
    {
        if (isset($data['save_action'])) {
            return $data['save_action'] instanceof ProductSaveAction
                ? $data['save_action']
                : ProductSaveAction::from($data['save_action']);
        }

        return ! empty($data['is_active'])
            ? ProductSaveAction::Submit
            : ProductSaveAction::Draft;
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
     * @param  array<string, mixed>  $data
     */
    private function validateLocalModeration(array $data, Category $category): void
    {
        $product = new Product($data);
        $product->forceFill([
            'category_id' => $category->id,
            'moderation_version' => 1,
        ]);
        $analysis = $this->localModerationAnalyzer->analyze($product);
        $blockingMessages = collect($analysis['findings'])
            ->where('blocking', true)
            ->pluck('message')
            ->all();

        if ($blockingMessages !== []) {
            $this->fail(['name' => implode(' ', $blockingMessages)], $category);
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
