<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seller;
use Illuminate\View\View;
use App\Models\Category;

class SellerController extends Controller
{
        public function shops(): View
    {
        $sellers = Seller::orderBy('product_count', 'desc')->get();

        return view('sellers', compact('sellers'));
    }

        public function seller(string $alias): View
    {
        $seller = Seller::where('alias', $alias)->firstOrFail();
        // 1. Получаем все категории 1-го уровня
        $categories = Category::whereNull('parent_id')->get();

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


        return view('seller', compact('seller', 'categories'));
    }
}
