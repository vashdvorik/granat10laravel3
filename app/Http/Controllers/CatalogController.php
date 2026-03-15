<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CatalogController extends Controller
{
    public function catalog(Request $request)
    {
        // Получаем минимальную и максимальную цену (из кеша)
        $priceRange = $this->getCachedPriceRange();
        $minPrice = $priceRange['min'];
        $maxPrice = $priceRange['max'];

        // Фильтры из сессии
        $selectedSellerAlias = $request->input('seller');
        $priceRange = Session::get('catalog_filters.price', [
            'min' => $minPrice,
            'max' => $maxPrice,
        ]);

        // Сохранение фильтров
        if ($request->has('seller')) {
            Session::put('catalog_filters.seller', $selectedSellerAlias);
        } elseif ($request->has('clear_seller')) {
            Session::forget('catalog_filters.seller');
            $selectedSellerAlias = null;
        }

        if ($request->has('min_price') && $request->has('max_price')) {
            Session::put('catalog_filters.price', [
                'min' => $request->input('min_price'),
                'max' => $request->input('max_price'),
            ]);
        }

        // Запрос товаров с фильтрами
        $query = Product::visible()
            ->with(['images', 'seller']);

        // Фильтр по продавцу
        if ($selectedSellerAlias) {
            $seller = Seller::getCachedByAlias($selectedSellerAlias);
            if ($seller) {
                $query->where('seller_id', $seller->id);
            }
        }

        // Фильтр по цене
        if (!empty($priceRange)) {
            $query->whereBetween('price', [$priceRange['min'], $priceRange['max']]);
        }

        // Сортировка: сначала VIP, потом обычные, по push_at
        $query->orderByDesc('is_vip')
              ->orderByDesc('push_at');

        // Пагинация (12 товаров на страницу)
        $products = $query->paginate(12)->appends($request->query());

        // 🆕 NESTED SET: Загрузка категорий одним запросом
        $categories = Category::getCachedRootTree();

        // 🆕 Фильтруем категории без товаров (если включено в настройках)
        if (config('categories.hide_empty', true)) {
            $categories = $this->filterEmptyCategories($categories);
        }

        // Продавцы
        $sellers = Seller::getCachedAll();
        
        // Подсчет товаров (теперь быстрее)
        $categoryProductsCounts = [];
        foreach ($categories as $category) {
            $categoryProductsCounts[$category->id] = $this->getCategoryProductsCount($category);
        }

        return view('catalog.index', compact(
            'categories', 
            'products', 
            'sellers', 
            'minPrice', 
            'maxPrice', 
            'categoryProductsCounts'
        ));
    }


    public function show($alias, Request $request)
    {
        // Найти категорию
        $category = Category::where('alias', $alias)
            ->where('is_active', true)
            ->firstOrFail();

        // Фильтры
        $priceRange = $this->getCachedPriceRange();
        $minPrice = $priceRange['min'];
        $maxPrice = $priceRange['max'];
        $selectedSellerAlias = $request->input('seller');
        $priceRange = Session::get('catalog_filters.price', [
            'min' => $minPrice,
            'max' => $maxPrice,
        ]);

        // Сохранение фильтров
        if ($request->has('seller')) {
            Session::put('catalog_filters.seller', $selectedSellerAlias);
        } elseif ($request->has('clear_seller')) {
            Session::forget('catalog_filters.seller');
            $selectedSellerAlias = null;
        }

        if ($request->has('min_price') && $request->has('max_price')) {
            Session::put('catalog_filters.price', [
                'min' => $request->input('min_price'),
                'max' => $request->input('max_price'),
            ]);
        }

        // 🆕 NESTED SET: Хлебные крошки
        $breadcrumbs = $category->breadcrumbs;

        // 🆕 NESTED SET: Все ID потомков + текущая категория (1 запрос!)
        $categoryIds = Category::descendantsAndSelf($category->id)->pluck('id')->toArray();
        
        // Запрос товаров с фильтрами
        $query = Product::visible()
            ->whereIn('category_id', $categoryIds)
            ->with(['images', 'seller']);

        // Фильтр по продавцу
        if ($selectedSellerAlias) {
            $seller = Seller::getCachedByAlias($selectedSellerAlias);
            if ($seller) {
                $query->where('seller_id', $seller->id);
            }
        }

        // Фильтр по цене
        if (!empty($priceRange)) {
            $query->whereBetween('price', [$priceRange['min'], $priceRange['max']]);
        }

        // Сортировка: сначала VIP, потом обычные, по push_at
        $query->orderByDesc('is_vip')
              ->orderByDesc('push_at');

        // Пагинация (12 товаров на страницу)
        $products = $query->paginate(12)->appends($request->query());

        // 🆕 NESTED SET: Подкатегории (только прямые дети)
        $hasChildren = $category->children->count() > 0;
        $menuCategories = $hasChildren
            ? $category->children
            : ($category->parent ? $category->parent->children : collect());

        // 🆕 Фильтруем пустые категории в меню (если включено в настройках)
        if (config('categories.hide_empty', true)) {
            $menuCategories = $this->filterEmptyCategories($menuCategories);
        }

        // Продавцы - только с активными пакетами и товарами в категории
        $sellers = Seller::whereHas('sellerPackages', function ($q) {
                $q->where('is_active', true)
                    ->where(function($dateQuery) {
                        $dateQuery->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    });
            })
            ->withCount(['products as product_count_in_category' => function ($q) use ($categoryIds) {
                $q->where('is_active', true)
                    ->whereIn('category_id', $categoryIds);
            }])
            ->having('product_count_in_category', '>', 0)
            ->orderByDesc('product_count_in_category')
            ->get();

        // Подсчет товаров по каждой категории в меню (категория + все потомки)
        $categoryProductsCounts = [];
        foreach ($menuCategories as $menuCategory) {
            $categoryProductsCounts[$menuCategory->id] = $this->getCategoryProductsCount($menuCategory);
        }

        // Подсчет товаров текущей категории
        $categoryProductsCount = $this->getCategoryProductsCount($category);

        return view('catalog.show', [
            'category' => $category,
            'categories' => $menuCategories,
            'breadcrumbs' => $breadcrumbs,
            'products' => $products,
            'sellers' => $sellers,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'categoryProductsCount' => $categoryProductsCount,
            'categoryProductsCounts' => $categoryProductsCounts,
            'hasChildren' => $hasChildren,
        ]);
    }

    public function sale()
    {
        // Получить товары
        $products = Product::visible()
            ->whereNotNull('lowprice') // Только со скидкой
            ->orderByDesc('sale_started_at')
            ->with(['images', 'seller'])
            ->paginate(12);

        return view('salepage', [
            'products' => $products,
        ]);
    }

    /**
     * 🆕 NESTED SET: Быстрый сбор ID (заменяет рекурсию)
     */
    private function collectCategoryIds($category)
    {
        // Используем статический метод с ID категории
        return Category::descendantsAndSelf($category->id)->pluck('id')->toArray();
    }

    /**
     * 🆕 NESTED SET: Оптимизированный подсчет товаров
     */
    private function getCategoryProductsCount($category)
    {
        return Cache::remember('category_products_count:' . $category->id, now()->addMinutes(10), function () use ($category) {
            $categoryIds = Category::descendantsAndSelf($category->id)->pluck('id');
            return Product::visible()
                ->whereIn('category_id', $categoryIds)
                ->count();
        });
    }

    /**
     * Получить закешированный диапазон цен
     */
    private function getCachedPriceRange()
    {
        return Cache::remember('product_price_range', now()->addHours(1), function () {
            return [
                'min' => Product::min('price') ?? 0,
                'max' => Product::max('price') ?? 0,
            ];
        });
    }

    /**
     * 🆕 Фильтрует категории без товаров
     * Рекурсивно проверяет наличие товаров в категории и её детях
     * 
     * @param \Illuminate\Support\Collection $categories
     * @return \Illuminate\Support\Collection
     */
    private function filterEmptyCategories($categories)
    {
        return $categories->filter(function ($category) {
            // Рекурсивно фильтруем детей
            if ($category->children->isNotEmpty()) {
                $filteredChildren = $this->filterEmptyCategories($category->children);
                $category->setRelation('children', $filteredChildren);
            }

            // Проверяем наличие товаров в категории или хотя бы в одном ребёнке
            return $this->categoryHasProducts($category);
        });
    }

    /**
     * 🆕 Проверяет, есть ли у категории товары (включая в детях)
     * 
     * @param Category $category
     * @return bool
     */
    private function categoryHasProducts($category)
    {
        // Получаем все ID потомков + текущая категория
        $categoryIds = $this->collectCategoryIds($category);
        
        // Проверяем наличие хотя бы одного товара
        return Product::visible()
            ->whereIn('category_id', $categoryIds)
            ->exists();
    }

    /**
     * 📱 AJAX: Загрузка дополнительных товаров (общий каталог)
     */
    public function loadMoreCatalog(Request $request)
    {
        $selectedSellerAlias = $request->input('seller');
        $priceRange = Session::get('catalog_filters.price');

        // Запрос товаров с фильтрами (копируем логику из catalog())
        $query = Product::visible()
            ->with(['images', 'seller']);

        // Фильтр по продавцу
        if ($selectedSellerAlias) {
            $seller = Seller::getCachedByAlias($selectedSellerAlias);
            if ($seller) {
                $query->where('seller_id', $seller->id);
            }
        }

        // Фильтр по цене
        if (!empty($priceRange)) {
            $query->whereBetween('price', [$priceRange['min'], $priceRange['max']]);
        }

        // Сортировка: сначала VIP, потом обычные, по push_at
        $query->orderByDesc('is_vip')
              ->orderByDesc('push_at');

        // Пагинация (12 товаров на страницу)
        $products = $query->paginate(12)->appends($request->query());

        // Возвращаем HTML
        return view('components.products-load-more', compact('products'))->render();
    }

    /**
     * 📱 AJAX: Загрузка дополнительных товаров (конкретная категория)
     */
    public function loadMoreCategory($alias, Request $request)
    {
        // Найти категорию
        $category = Category::where('alias', $alias)
            ->where('is_active', true)
            ->firstOrFail();

        $selectedSellerAlias = $request->input('seller');
        $priceRange = Session::get('catalog_filters.price');

        // Все ID потомков + текущая категория
        $categoryIds = Category::descendantsAndSelf($category->id)->pluck('id')->toArray();
        
        // Запрос товаров с фильтрами (копируем логику из show())
        $query = Product::visible()
            ->whereIn('category_id', $categoryIds)
            ->with(['images', 'seller']);

        // Фильтр по продавцу
        if ($selectedSellerAlias) {
            $seller = Seller::getCachedByAlias($selectedSellerAlias);
            if ($seller) {
                $query->where('seller_id', $seller->id);
            }
        }

        // Фильтр по цене
        if (!empty($priceRange)) {
            $query->whereBetween('price', [$priceRange['min'], $priceRange['max']]);
        }

        // Сортировка: сначала VIP, потом обычные, по push_at
        $query->orderByDesc('is_vip')
              ->orderByDesc('push_at');

        // Пагинация (12 товаров на страницу)
        $products = $query->paginate(12)->appends($request->query());

        // Возвращаем HTML
        return view('components.products-load-more', compact('products'))->render();
    }

    /**
     * 📱 AJAX: Загрузка дополнительных товаров (страница распродаж)
     */
    public function loadMoreSale(Request $request)
    {
        // Запрос товаров со скидкой (копируем логику из sale())
        $products = Product::visible()
            ->whereNotNull('lowprice') // Только со скидкой
            ->orderByDesc('sale_started_at')
            ->with(['images', 'seller'])
            ->paginate(12);

        // Возвращаем HTML
        return view('components.products-load-more', compact('products'))->render();
    }

    /**
     * Поиск товаров по названию и описанию
     */
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));

        $products = Product::visible()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'LIKE', '%' . $q . '%')
                        ->orWhere('desc', 'LIKE', '%' . $q . '%')
                        ->orWhere('features1', 'LIKE', '%' . $q . '%')
                        ->orWhere('features2', 'LIKE', '%' . $q . '%')
                        ->orWhere('features3', 'LIKE', '%' . $q . '%')
                        ->orWhere('features4', 'LIKE', '%' . $q . '%');
                });
            })
            ->orderByDesc('is_vip')
            ->orderByDesc('push_at')
            ->with(['images', 'seller'])
            ->paginate(12)
            ->appends(['q' => $q]);

        return view('search', compact('products', 'q'));
    }

    /**
     * AJAX: Загрузка следующей страницы результатов поиска
     */
    public function loadMoreSearch(Request $request)
    {
        $q = trim($request->input('q', ''));

        $products = Product::visible()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'LIKE', '%' . $q . '%')
                        ->orWhere('desc', 'LIKE', '%' . $q . '%')
                        ->orWhere('features1', 'LIKE', '%' . $q . '%')
                        ->orWhere('features2', 'LIKE', '%' . $q . '%')
                        ->orWhere('features3', 'LIKE', '%' . $q . '%')
                        ->orWhere('features4', 'LIKE', '%' . $q . '%');
                });
            })
            ->orderByDesc('is_vip')
            ->orderByDesc('push_at')
            ->with(['images', 'seller'])
            ->paginate(12)
            ->appends(['q' => $q]);

        return view('components.products-load-more', compact('products'))->render();
    }
}