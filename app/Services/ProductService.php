<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Seller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для работы с продуктами
 * Содержит всю бизнес-логику управления продуктами
 */
class ProductService
{
    public function __construct(
        private ImageService $imageService
    ) {}

    /**
     * Создание нового продукта
     */
    public function createProduct(Seller $seller, array $data): Product
    {
        return DB::transaction(function () use ($seller, $data) {
            // Проверка лимитов пакета
            if (! $seller->canActivateProduct()) {
                throw new \DomainException(__('errors.product_limit_reached'));
            }

            // Создание продукта
            $product = new Product;
            $product->fill($data);
            $product->seller_id = $seller->id;
            $product->user_id = $seller->user_id;
            $product->is_active = true;
            $product->save();

            // Обработка изображений
            if (! empty($data['images'])) {
                $this->imageService->attachImages($product, $data['images']);
            }

            // Установка главного изображения
            if (! empty($data['main_image_id'])) {
                $this->imageService->setMainImage($product, (int) $data['main_image_id']);
            }

            // Очистка кэша
            $this->clearProductCaches($product);

            return $product->fresh(['images', 'category', 'seller']);
        });
    }

    /**
     * Обновление продукта
     */
    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->fill($data);
            $product->save();

            // Обновление изображений если переданы
            if (isset($data['images'])) {
                $this->imageService->syncImages($product, $data['images']);
            }

            // Обновление главного изображения
            if (! empty($data['main_image_id'])) {
                $this->imageService->setMainImage($product, (int) $data['main_image_id']);
            }

            // Очистка кэша
            $this->clearProductCaches($product);

            return $product->fresh(['images', 'category', 'seller']);
        });
    }

    /**
     * Удаление продукта
     */
    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            // Удаление всех изображений
            $this->imageService->deleteAllImages($product);

            // Очистка кэша
            $this->clearProductCaches($product);

            // Удаление продукта
            return $product->delete();
        });
    }

    /**
     * Получение видимых продуктов с фильтрацией
     */
    public function getVisibleProducts(array $filters = [], int $perPage = 20, ?int $page = null): LengthAwarePaginator
    {
        $query = Product::visible()
            ->with(['images', 'category', 'seller']);

        // Фильтр по категории
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Фильтр по продавцу
        if (! empty($filters['seller_id'])) {
            $query->where('seller_id', $filters['seller_id']);
        }

        // Фильтр по распродаже (товары со скидкой)
        if (! empty($filters['on_sale'])) {
            $query->whereNotNull('lowprice')
                  ->where('lowprice', '>', 0)
                  ->whereRaw('lowprice < price');
        }

        // Поиск по названию
        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        // Фильтр по цене
        if (! empty($filters['price_from'])) {
            $query->where('price', '>=', $filters['price_from']);
        }
        if (! empty($filters['price_to'])) {
            $query->where('price', '<=', $filters['price_to']);
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация с учетом переданной страницы
        if ($page !== null) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->paginate($perPage);
    }

    /**
     * Получение продуктов продавца
     */
    public function getSellerProducts(Seller $seller, int $perPage = 20): LengthAwarePaginator
    {
        return Product::where('seller_id', $seller->id)
            ->with(['images', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Инкремент просмотров продукта
     */
    public function incrementViews(Product $product): void
    {
        $product->increment('views_count');

        // Обновляем кэш счетчика
        Cache::forget("product.{$product->id}.views");
    }

    /**
     * Очистка всех кэшей связанных с продуктом
     */
    private function clearProductCaches(Product $product): void
    {
        // Кэш самого продукта
        Cache::forget("product.{$product->id}");

        // Кэш списка продуктов категории
        Cache::forget("category.{$product->category_id}.products");

        // Кэш продуктов продавца
        Cache::forget("seller.{$product->seller_id}.products");

        // Кэш дерева категорий (если в нем есть счетчики)
        Cache::forget('categories.tree');

        // Статический кэш в модели
        Product::clearProductCaches();
    }

    /**
     * Получение похожих продуктов
     */
    public function getSimilarProducts(Product $product, int $limit = 6): Collection
    {
        return Cache::remember(
            "product.{$product->id}.similar",
            now()->addHours(6),
            fn () => Product::visible()
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->inRandomOrder()
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Проверка возможности активации продукта (лимиты пакета)
     *
     * @return array{allowed: bool, message: string, current: int, limit: int}
     */
    public function canActivateProduct(Seller $seller): array
    {
        return $seller->canActivateProduct();
    }

    /**
     * Проверка возможности активации VIP продукта
     *
     * @return array{allowed: bool, message: string, current: int, limit: int}
     */
    public function canActivateVipProduct(Seller $seller): array
    {
        return $seller->canActivateVipProduct();
    }
}
