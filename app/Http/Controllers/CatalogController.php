<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Seller;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * Контроллер каталога товаров (публичная часть)
 */
class CatalogController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private CategoryService $categoryService
    ) {}

    /**
     * Главная страница каталога
     */
    public function catalog(Request $request)
    {
        // Получаем диапазон цен
        $priceRange = $this->getCachedPriceRange();

        // Применяем фильтры из сессии/запроса
        $filters = $this->prepareFilters($request, $priceRange);

        // Получаем товары через сервис
        $products = $this->productService->getVisibleProducts($filters, 12);

        // Получаем категории
        $categories = $this->categoryService->getCategoryTree();

        // Фильтруем пустые категории если нужно
        if (config('categories.hide_empty', true)) {
            $categories = $this->categoryService->filterTreeByProducts($categories);
        }

        // Список продавцов
        $sellers = Seller::getCachedAll();

        // Счетчики товаров по категориям
        $categoryProductsCounts = $this->getCategoryProductsCounts($categories);

        return view('catalog.index', compact(
            'categories',
            'products',
            'sellers',
            'categoryProductsCounts'
        ));
    }

    /**
     * Страница категории
     */
    public function show(string $alias, Request $request)
    {
        // Получаем категорию через сервис
        $category = $this->categoryService->getCategoryBySlug($alias);

        if (! $category || ! $category->is_active) {
            abort(404, __('errors.category_not_found'));
        }

        // Хлебные крошки
        $breadcrumbs = $this->categoryService->getBreadcrumbs($category);

        // Дочерние категории
        $childCategories = $this->categoryService->getChildren($category);

        // Фильтры
        $priceRange = $this->getCachedPriceRange();
        $filters = $this->prepareFilters($request, $priceRange);
        $filters['category_id'] = $category->id;

        // Товары категории
        $products = $this->productService->getVisibleProducts($filters, 12);

        // SEO данные
        $metaTitle = $category->meta_title ?: $category->name;
        $metaDescription = $category->meta_description ?: ($category->description ?? '');

        return view('catalog.category', compact(
            'category',
            'breadcrumbs',
            'childCategories',
            'products',
            'metaTitle',
            'metaDescription'
        ));
    }

    /**
     * Поиск товаров
     */
    public function search(Request $request)
    {
        $searchQuery = $request->input('q', '');

        if (empty($searchQuery) || strlen($searchQuery) < 3) {
            return redirect()
                ->route('catalog')
                ->with('error', __('errors.search_query_too_short'));
        }

        // Фильтры для поиска
        $filters = [
            'search' => $searchQuery,
        ];

        // Применяем дополнительные фильтры
        if ($request->has('category_id')) {
            $filters['category_id'] = $request->input('category_id');
        }

        if ($request->has('price_from')) {
            $filters['price_from'] = $request->input('price_from');
        }

        if ($request->has('price_to')) {
            $filters['price_to'] = $request->input('price_to');
        }

        // Поиск через сервис
        $products = $this->productService->getVisibleProducts($filters, 24);

        // Категории для фильтра
        $categories = $this->categoryService->getRootCategories();

        return view('catalog.search', compact(
            'products',
            'categories',
            'searchQuery'
        ));
    }

    /**
     * Подготовка фильтров из запроса и сессии
     */
    private function prepareFilters(Request $request, array $priceRange): array
    {
        $filters = [];

        // Фильтр по продавцу
        if ($request->has('seller')) {
            $sellerAlias = $request->input('seller');
            Session::put('catalog_filters.seller', $sellerAlias);

            $seller = Seller::getCachedByAlias($sellerAlias);
            if ($seller) {
                $filters['seller_id'] = $seller->id;
            }
        } elseif ($request->has('clear_seller')) {
            Session::forget('catalog_filters.seller');
        } elseif (Session::has('catalog_filters.seller')) {
            $seller = Seller::getCachedByAlias(Session::get('catalog_filters.seller'));
            if ($seller) {
                $filters['seller_id'] = $seller->id;
            }
        }

        // Фильтр по цене
        if ($request->has('min_price') && $request->has('max_price')) {
            $filters['price_from'] = $request->input('min_price');
            $filters['price_to'] = $request->input('max_price');

            Session::put('catalog_filters.price', [
                'min' => $filters['price_from'],
                'max' => $filters['price_to'],
            ]);
        } elseif (Session::has('catalog_filters.price')) {
            $sessionPrice = Session::get('catalog_filters.price');
            $filters['price_from'] = $sessionPrice['min'];
            $filters['price_to'] = $sessionPrice['max'];
        } else {
            $filters['price_from'] = $priceRange['min'];
            $filters['price_to'] = $priceRange['max'];
        }

        // Сортировка
        $filters['sort_by'] = $request->input('sort_by', 'created_at');
        $filters['sort_order'] = $request->input('sort_order', 'desc');

        return $filters;
    }

    /**
     * Получение диапазона цен с кешированием
     */
    private function getCachedPriceRange(): array
    {
        return Cache::remember(
            'products.price_range',
            now()->addHours(6),
            function () {
                $min = \DB::table('products')
                    ->where('is_active', true)
                    ->min('price') ?? 0;

                $max = \DB::table('products')
                    ->where('is_active', true)
                    ->max('price') ?? 100000;

                return [
                    'min' => (float) $min,
                    'max' => (float) $max,
                ];
            }
        );
    }

    /**
     * Подсчет товаров по категориям с кешированием
     */
    private function getCategoryProductsCounts($categories): array
    {
        return Cache::remember(
            'categories.products_counts',
            now()->addHours(1),
            function () use ($categories) {
                $counts = [];

                foreach ($categories as $category) {
                    $counts[$category->id] = $this->getCategoryProductsCount($category);
                }

                return $counts;
            }
        );
    }

    /**
     * Рекурсивный подсчет товаров в категории и подкатегориях
     */
    private function getCategoryProductsCount($category): int
    {
        $count = \DB::table('products')
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->count();

        if (isset($category->children)) {
            foreach ($category->children as $child) {
                $count += $this->getCategoryProductsCount($child);
            }
        }

        return $count;
    }

    /**
     * Страница распродажи (товары со скидкой)
     */
    public function sale(Request $request)
    {
        $filters = [
            'on_sale' => true,
        ];

        // Применяем дополнительные фильтры
        $priceRange = $this->getCachedPriceRange();
        $filters = array_merge($filters, $this->prepareFilters($request, $priceRange));

        // Получаем товары со скидкой
        $products = $this->productService->getVisibleProducts($filters, 24);

        // Категории для фильтра
        $categories = $this->categoryService->getRootCategories();

        return view('catalog.sale', compact('products', 'categories'));
    }

    /**
     * AJAX подгрузка товаров каталога
     */
    public function loadMoreCatalog(Request $request)
    {
        $page = $request->input('page', 1);
        $priceRange = $this->getCachedPriceRange();
        $filters = $this->prepareFilters($request, $priceRange);

        $products = $this->productService->getVisibleProducts($filters, 12, $page);

        return response()->json([
            'html' => view('catalog.partials.products', compact('products'))->render(),
            'hasMore' => $products->hasMorePages(),
        ]);
    }

    /**
     * AJAX подгрузка товаров категории
     */
    public function loadMoreCategory(string $alias, Request $request)
    {
        $category = $this->categoryService->getCategoryBySlug($alias);

        if (! $category) {
            return response()->json(['error' => 'Category not found'], 404);
        }

        $page = $request->input('page', 1);
        $priceRange = $this->getCachedPriceRange();
        $filters = $this->prepareFilters($request, $priceRange);
        $filters['category_id'] = $category->id;

        $products = $this->productService->getVisibleProducts($filters, 12, $page);

        return response()->json([
            'html' => view('catalog.partials.products', compact('products'))->render(),
            'hasMore' => $products->hasMorePages(),
        ]);
    }

    /**
     * AJAX подгрузка товаров распродажи
     */
    public function loadMoreSale(Request $request)
    {
        $page = $request->input('page', 1);
        $filters = ['on_sale' => true];

        $priceRange = $this->getCachedPriceRange();
        $filters = array_merge($filters, $this->prepareFilters($request, $priceRange));

        $products = $this->productService->getVisibleProducts($filters, 24, $page);

        return response()->json([
            'html' => view('catalog.partials.products', compact('products'))->render(),
            'hasMore' => $products->hasMorePages(),
        ]);
    }

    /**
     * AJAX подгрузка результатов поиска
     */
    public function loadMoreSearch(Request $request)
    {
        $searchQuery = $request->input('q', '');
        $page = $request->input('page', 1);

        if (empty($searchQuery) || strlen($searchQuery) < 3) {
            return response()->json(['error' => 'Query too short'], 400);
        }

        $filters = ['search' => $searchQuery];

        if ($request->has('category_id')) {
            $filters['category_id'] = $request->input('category_id');
        }

        $products = $this->productService->getVisibleProducts($filters, 24, $page);

        return response()->json([
            'html' => view('catalog.partials.products', compact('products'))->render(),
            'hasMore' => $products->hasMorePages(),
        ]);
    }
}
