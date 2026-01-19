<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/*
catalog(Request $request) - Отображает главную страницу каталога со всеми
товарами, фильтрует по продавцам и ценам, сохраняет фильтры в сессии, 
разделяет VIP и обычные товары, загружает дерево категорий до 8 уровней 
вложенности.

show($alias, Request $request) - Показывает товары определенной категории 
по её alias, применяет фильтры по продавцам и ценам, собирает хлебные крошки,
получает товары из текущей категории и всех подкатегорий, показывает 
подкатегории или родственные категории.

sale() - Отображает страницу распродажи с товарами, у которых есть скидка 
(поле lowprice заполнено).
*/
class CatalogController extends Controller
{
    public function catalog(Request $request)
    {
        // Получаем минимальную и максимальную цену
        $minPrice = Product::min('price');
        $maxPrice = Product::max('price');

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

        // VIP товары
        $vipProducts = Product::where('is_active', true)
            ->where('is_vip', true)
            ->orderByDesc('push_at')
            ->with(['images', 'seller'])
            ->get();

        // Обычные товары
        $normalProducts = Product::where('is_active', true)
            ->where('is_vip', false)
            ->orderByDesc('push_at')
            ->with(['images', 'seller'])
            ->get();

        // Объединение
        $allProducts = $vipProducts->concat($normalProducts);

        // Применение фильтров
        $products = $allProducts->filter(function ($product) use ($selectedSellerAlias, $priceRange) {
            if ($selectedSellerAlias) {
                $seller = Seller::where('alias', $selectedSellerAlias)->first();
                if (!$seller || $product->seller_id != $seller->id) {
                    return false;
                }
            }

            if (!empty($priceRange)) {
                if ($product->price < $priceRange['min'] || $product->price > $priceRange['max']) {
                    return false;
                }
            }

            return true;
        });

        // 🆕 NESTED SET: Загрузка категорий одним запросом
        $categories = Category::whereIsRoot()
            ->where('is_active', true)
            ->defaultOrder()
            ->get()
            ->toTree();

        // Продавцы
        $sellers = Seller::orderBy('product_count', 'desc')->get();
        
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
        $minPrice = Product::min('price');
        $maxPrice = Product::max('price');
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
        $categoryIds = Category::whereDescendantOrSelf($category->id)->pluck('id')->toArray();
        // VIP товары
        $vipProducts = Product::where('is_active', true)
            ->where('is_vip', true)
            ->whereIn('category_id', $categoryIds)
            ->orderByDesc('push_at')
            ->with(['images', 'seller'])
            ->get();

        // Обычные товары
        $normalProducts = Product::where('is_active', true)
            ->where('is_vip', false)
            ->whereIn('category_id', $categoryIds)
            ->orderByDesc('push_at')
            ->with(['images', 'seller'])
            ->get();

        $products = $vipProducts->concat($normalProducts);

        // Применение фильтров
        $products = $products->filter(function ($product) use ($selectedSellerAlias, $priceRange) {
            if ($selectedSellerAlias) {
                $seller = Seller::where('alias', $selectedSellerAlias)->first();
                if (!$seller || $product->seller_id != $seller->id) {
                    return false;
                }
            }

            if (!empty($priceRange)) {
                if ($product->price < $priceRange['min'] || $product->price > $priceRange['max']) {
                    return false;
                }
            }

            return true;
        });

        // 🆕 NESTED SET: Подкатегории (только прямые дети)
        $menuCategories = $category->children->count() > 0
            ? $category->children
            : ($category->parent ? $category->parent->children : collect());

        // Продавцы
        $sellers = Seller::select('sellers.*', \DB::raw('COUNT(products.id) as product_count_in_category'))
            ->leftJoin('products', 'sellers.id', '=', 'products.seller_id')
            ->whereIn('products.category_id', $categoryIds)
            ->where('products.is_active', true)
            ->groupBy('sellers.id')
            ->orderBy('product_count_in_category', 'desc')
            ->get();

        // Подсчет товаров
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
        ]);
    }

    public function sale()
    {
        // Получить товары
        $products = Product::where('is_active', true)
            ->whereNotNull('lowprice') // Только со скидкой
            ->orderByDesc('sale_started_at')
            ->with(['images', 'seller'])
            ->get();

        return view('salepage', [
            'products' => $products,
        ]);
    }

/**
 * 🆕 NESTED SET: Быстрый сбор ID (заменяет рекурсию)
 */
    private function collectCategoryIds($category)
    {
        return Category::whereDescendantOrSelf($category->id)->pluck('id')->toArray();
    }

/**
 * 🆕 NESTED SET: Оптимизированный подсчет товаров
 */
    private function getCategoryProductsCount($category)
    {
        $categoryIds = Category::whereDescendantOrSelf($category->id)->pluck('id');
        return Product::whereIn('category_id', $categoryIds)
            ->where('is_active', true)
            ->count();
    }

}