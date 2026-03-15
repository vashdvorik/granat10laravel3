<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seller;
use App\Models\Product;
use Illuminate\View\View;
use App\Models\Category;

class SellerController extends Controller
{
    /*
     Отображает список всех магазинов, отсортированных по количеству товаров
    */
        public function shops(): View
    {
        $sellers = Seller::withCount(['products' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('products_count', 'desc')
            ->paginate(12);
            
        return view('sellers', compact('sellers'));
    }
    /*
    seller(string $alias) - Показывает страницу конкретного магазина по alias, 
    фильтрует категории первого уровня, показывая только те, 
    в которых есть товары данного продавца, рекурсивно подсчитывает 
    товары во всех подкатегориях. 
    */
    public function seller(string $alias): View
    {
        $seller = Seller::getCachedByAlias($alias);  
        if (!$seller) {
            abort(404);
        }
    
        // Получаем категории из кеша
        $categories = Category::getCachedRootCategories();

        // 2. Создаем функцию для подсчета товаров продавца во всех дочерних категориях
        function countSellerProductsInCategory(Category $category, Seller $seller) {
            $count = $category->products()->where('seller_id', $seller->id)->count();

            foreach ($category->children as $child) {
                $count += countSellerProductsInCategory($child, $seller);
            }

            return $count;
        }

        // 3. Фильтруем категории 1-го уровня и добавляем количество товаров
        $categories = $categories->filter(function ($category) use ($seller, &$countSellerProductsInCategory) {
            $totalProducts = countSellerProductsInCategory($category, $seller);
            $category->total_products = $totalProducts; // Добавляем атрибут total_products к категории
            return $totalProducts > 0;
        });


        $products = Product::visible()
            ->where('seller_id', $seller->id)
            ->with(['images'])
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('seller', compact('seller', 'categories', 'products'));
    }

    /**
     * 📱 AJAX: Загрузка товаров продавца (для мобильных)
     */
    public function loadMoreSellerProducts(string $alias, Request $request)
    {
        $seller = Seller::getCachedByAlias($alias);
        if (!$seller) {
            abort(404);
        }

        $products = Product::visible()
            ->where('seller_id', $seller->id)
            ->with(['images'])
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('components.products-load-more', compact('products'))->render();
    }

    /**
     * 📱 AJAX: Загрузка дополнительных продавцов (для мобильных)
     */
    public function loadMoreShops(Request $request)
    {
        $sellers = Seller::withCount(['products' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('products_count', 'desc')
            ->paginate(12);

        // Возвращаем HTML (создадим отдельный partial)
        return view('components.sellers-load-more', compact('sellers'))->render();
    }
}
