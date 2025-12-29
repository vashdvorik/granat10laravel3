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
    // Получаем минимальную и максимальную цену из базы данных
    $minPrice = Product::min('price');
    $maxPrice = Product::max('price');

    // Получаем фильтры из сессии
    $selectedSellers = Session::get('catalog_filters.sellers', []);
    $priceRange = Session::get('catalog_filters.price', []);

    // Если фильтры цены не установлены, устанавливаем значения по умолчанию
    if (empty($priceRange)) {
        $priceRange = [
            'min' => $minPrice,
            'max' => $maxPrice,
        ];
    }

    // Получаем фильтр продавца из запроса
    $selectedSellerAlias = $request->input('seller');

    // Сохраняем фильтры в сессии
    if ($request->has('seller')) {
        Session::put('catalog_filters.seller', $selectedSellerAlias);
        } elseif ($request->has('clear_seller')) {
            Session::forget('catalog_filters.seller');
            $selectedSellerAlias = null;
        }

    
    // Сохраняем фильтры цены в сессии, если они переданы через форму
    if ($request->has('min_price') && $request->has('max_price')) {
        Session::put('catalog_filters.price', [
            'min' => $request->input('min_price'),
            'max' => $request->input('max_price'),
        ]);
        } elseif ($request->has('clear_price')) {
            Session::forget('catalog_filters.price');
            $priceRange = [];
        }

    // Получаем VIP-товары (без фильтров)
    $vipProducts = Product::where('is_active', true)
        ->where('is_vip', true)
        ->orderByDesc('push_at')
        ->with(['images', 'seller'])
        ->get();

    // Получаем обычные товары (без фильтров)
    $normalProducts = Product::where('is_active', true)
        ->where('is_vip', false)
        ->orderByDesc('push_at')
        ->with(['images', 'seller'])
        ->get();

    
    // Объединить VIP и обычные товары
    $allProducts = $vipProducts->concat($normalProducts);

    // Применяем фильтры к списку товаров
    $products = $allProducts->filter(function ($product) use ($selectedSellerAlias, $priceRange) {
        // Фильтр по продавцу
        if ($selectedSellerAlias) {
            $seller = Seller::where('alias', $selectedSellerAlias)->first();
            if (!$seller || $product->seller_id != $seller->id) {
                return false;
            }
        }

        // Фильтр по цене
        if (!empty($priceRange)) {
            if ($product->price < $priceRange['min'] || $product->price > $priceRange['max']) {
                return false;
            }
        }

        return true;
    });

    // Загрузка всех корневых категорий с рекурсивными детьми
    $categories = Category::whereNull('parent_id')
        ->where('is_active', true)
        ->with([
            'children' => function($q) {
            $q->orderBy('number');
        
            $q->with(['children' => function($q2) {
            $q2->orderBy('number');
            
            $q2->with(['children' => function($q3) {
            $q3->orderBy('number');
            
            $q3->with(['children' => function($q4) {
            $q4->orderBy('number');
            
            $q4->with(['children' => function($q5) {
            $q5->orderBy('number');
            
            $q5->with(['children' => function($q6) {
            $q6->orderBy('number');
            
            $q6->with(['children' => function($q7) {
            $q7->orderBy('number');
            
            $q7->with(['children' => function($q8) {
            $q8->orderBy('number');
            
                }]);
                }]);
                }]);
                }]);
                }]);
                }]);
                }]);
            }
        ])
        ->orderBy('number')
        ->get();

    
    
    // Получаем список продавцов, у которых есть товары
    $sellers = Seller::orderBy('product_count', 'desc')->get();
    
    // Получаем количество товаров для каждой категории
    $categoryProductsCounts = [];
        foreach ($categories as $category) {
            $categoryProductsCounts[$category->id] = $this->getCategoryProductsCount($category);
        }

    // Возвращаем представление с данными
    return view('catalog.index', compact('categories', 'products', 'sellers', 'minPrice', 'maxPrice', 'categoryProductsCounts'));
}


    public function show($alias, Request $request)
    {
        // Найти текущую категорию по алиасу
        $category = Category::where('alias', $alias)
            ->where('is_active', true)
            ->with([
                'children' => function($q) {
                    $q->orderBy('number');
                    $q->with(['children' => function($q2) {
                    $q2->orderBy('number');
                    $q2->with(['children' => function($q3) {
                    $q3->orderBy('number');
                    $q3->with(['children' => function($q4) {
                    $q4->orderBy('number');
                    $q4->with(['children' => function($q5) {
                    $q5->orderBy('number');
                    $q5->with(['children' => function($q6) {
                    $q6->orderBy('number');
                    $q6->with(['children' => function($q7) {
                    $q7->orderBy('number');
                    $q7->with(['children' => function($q8) {
                    $q8->orderBy('number');
                    }]);
                    }]);
                    }]);
                    }]);
                    }]);
                    }]);
                    }]);
                },
                'parent.children' => function($q) {
                    $q->orderBy('number');
                }
            ])
            ->firstOrFail();
        
        // Получить минимальную и максимальную цену из базы данных
        $minPrice = Product::min('price');
        $maxPrice = Product::max('price');

        // Получаем фильтры из сессии
        $selectedSellers = Session::get('catalog_filters.sellers', []);
        $priceRange = Session::get('catalog_filters.price', []);

        // Если фильтры цены не установлены, устанавливаем значения по умолчанию
        if (empty($priceRange)) {
            $priceRange = [
                'min' => $minPrice,
                'max' => $maxPrice,
            ];
        }

        // Получаем фильтр продавца из запроса
        $selectedSellerAlias = $request->input('seller');

        // Сохраняем фильтры в сессии
        if ($request->has('seller')) {
            Session::put('catalog_filters.seller', $selectedSellerAlias);
        } elseif ($request->has('clear_seller')) {
            Session::forget('catalog_filters.seller');
            $selectedSellerAlias = null;
        }

         // Сохраняем фильтры цены в сессии, если они переданы через форму
        if ($request->has('min_price') && $request->has('max_price')) {
            Session::put('catalog_filters.price', [
                'min' => $request->input('min_price'),
                'max' => $request->input('max_price'),
            ]);
        } elseif ($request->has('clear_price')) {
            Session::forget('catalog_filters.price');
            $priceRange = [];
        }

        // Получить цепочку хлебных крошек
        $breadcrumbs = $category->breadcrumbs;

        // Собрать id текущей категории и всех её потомков
        $categoryIds = $this->collectCategoryIds($category);  
        
        // Получить VIP-товары
        $vipProducts = Product::where('is_active', true)
            ->where('is_vip', true)
            ->whereIn('category_id', $categoryIds)
            ->orderByDesc('push_at')
            ->with(['images', 'seller'])
            ->get();

        // Получить обычные товары
        $normalProducts = Product::where('is_active', true)
            ->where('is_vip', false)
            ->whereIn('category_id', $categoryIds)
            ->orderByDesc('push_at')
            ->with(['images', 'seller'])
            ->get();

        // Объединить VIP и обычные товары
        $products = $vipProducts->concat($normalProducts);

         // Применить фильтры к объединенному списку товаров
        $products = $products->filter(function ($product) use ($selectedSellerAlias, $priceRange) {
            // Фильтр по продавцу
            if ($selectedSellerAlias) {
                $seller = Seller::where('alias', $selectedSellerAlias)->first();
                if (!$seller || $product->seller_id != $seller->id) {
                    return false;
                }
            }

            // Фильтр по цене
            if (!empty($priceRange)) {
                if ($product->price < $priceRange['min'] || $product->price > $priceRange['max']) {
                    return false;
                }
            }

            return true;
        });

        // Если у категории есть подкатегории — показываем их, иначе показываем "сестер"
        $menuCategories = $category->children->count() > 0
        ? $category->children
        : ($category->parent ? $category->parent->children : collect());
        
        // Получить список продавцов, у которых есть товары в данной категории
    /*    $sellers = Seller::whereHas('products', function ($query) use ($categoryIds) {
        $query->whereIn('category_id', $categoryIds);
        })
        ->orderBy('product_count', 'desc')
        ->get(); */
        // Получить список продавцов, у которых есть товары в данной категории + колличество товара
        $sellers = Seller::select('sellers.*', \DB::raw('COUNT(products.id) as product_count_in_category'))
        ->leftJoin('products', 'sellers.id', '=', 'products.seller_id')
        ->whereIn('products.category_id', $categoryIds)
        ->where('products.is_active', true)
        ->groupBy('sellers.id')
        ->orderBy('product_count_in_category', 'desc')
        ->get();  

    // Сортируем продавцов по убыванию количества товаров
    $sellers = $sellers->sortByDesc('product_count_in_category');

    // Сортируем продавцов по убыванию количества товаров
    $sellers = $sellers->sortByDesc('product_count_in_category');

    // Получаем количество товаров для текущей категории и всех ее подкатегорий
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
 * Рекурсивно собирает id категории и всех её потомков
 */
    private function collectCategoryIds($category)
    {
        $ids = [$category->id];
        foreach ($category->children as $child) {
            $ids = array_merge($ids, $this->collectCategoryIds($child));
        }
        return $ids;
    }
/**
 * Рекурсивно подсчитывает количество товаров в категории и всех ее подкатегориях
 */
    private function getCategoryProductsCount($category)
    {
        $count = $category->products()->where('is_active', true)->count();
        foreach ($category->children as $child) {
            $count += $this->getCategoryProductsCount($child);
        }
        return $count;
    }

}