<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

/* show($alias) - Показывает детальную страницу товара по alias,
   загружает связанные данные (изображения, продавца, категорию), 
   формирует хлебные крошки для навигации. */

class ProductController extends Controller
{
       public function show($alias)
    {
        $product = Product::with(['images', 'seller', 'category'])->where('alias', $alias)->firstOrFail();
         // Получаем категорию товара
        $category = $product->category;

        // Получаем хлебные крошки для категории товара
        $breadcrumbs = $category ? $category->breadcrumbs : [];
        
        return view('product', compact('product', 'breadcrumbs', 'category'));
    }


}
