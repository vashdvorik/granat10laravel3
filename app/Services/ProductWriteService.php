<?php

namespace App\Services;

use App\Data\ProductWriteOutcome;
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
        private readonly ProductLocalModerationAnalyzer $localModerationAnalyzer,
        private readonly ProductStateTransitionService $transitions
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
                $submitForModeration,
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
        return $this->updateWithOutcome($product, $data, $images, $actor)->product;
    }

    /**
     * Сохраняет товар и сообщает, что реально произошло: ушёл ли он на проверку, менялось ли то,
     * что проверяется модерацией. По этому строится сообщение продавцу.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function updateWithOutcome(
        Product $product,
        array $data,
        array $images = [],
        ?User $actor = null
    ): ProductWriteOutcome {
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
                'product' => 'Товар изменился в другой вкладке или после автоматической проверки. '
                    .'Обновите страницу и проверьте изменения, чтобы ничего не потерять.',
            ]);
        }

        $statusBefore = $product->moderation_status;
        $wasPublic = $this->transitions->isPublic($product);

        $category = $this->validateCategory($data['category_id']);
        unset($data['images']);
        $saveAction = $this->saveAction($data);
        unset($data['save_action']);
        $data = $this->normalizeFlags($data);
        $submitForModeration = $saveAction->requestsModeration();
        $data['publication_requested'] = $submitForModeration;

        $this->validateLimits(
            $this->productLimitService->validateForUpdate(
                $submitForModeration,
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
                'save_action' => 'Вы изменили '.ProductStatusStoryFactory::RECHECK_FIELDS.': такие правки проверяются заново. '
                    .'Нажмите «Отправить на проверку» или «Сохранить черновик».',
            ], $category);
        }

        // Отправить на проверку можно товар из черновика или «на доработке», либо любой товар,
        // который из-за правок содержимого вернётся в черновик. Остальное — не отправка.
        $canBeSubmitted = $moderatedContentChanged || $this->transitions->canSubmit($product);

        if ($submitForModeration && ! $canBeSubmitted && $product->moderation_status === ProductModerationStatus::Rejected) {
            $this->fail([
                'save_action' => 'Товар отклонён. Чтобы отправить его на проверку снова, сначала исправьте карточку '
                    .'по причине отказа (она показана вверху страницы).',
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
            // Замечания прошлой проверки остаются в черновике, пока продавец не отправит товар снова
            $product = $this->productModerationService->invalidate($product, keepRemark: true);
        }

        $submitted = false;

        if ($submitForModeration && $this->transitions->canSubmit($product->refresh())) {
            if ($actor === null) {
                throw new \LogicException('Для отправки товара на модерацию требуется пользователь.');
            }

            $product = $this->productModerationService->submit($product, $actor);
            $submitted = true;
        }

        return new ProductWriteOutcome(
            product: $product->refresh(),
            submitted: $submitted,
            contentChanged: $moderatedContentChanged,
            statusBefore: $statusBefore,
            wasPublic: $wasPublic,
        );
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
        // VIP продавец не выставляет сам: он покупается отдельно за баланс
        unset($data['is_vip'], $data['vip_until']);
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
