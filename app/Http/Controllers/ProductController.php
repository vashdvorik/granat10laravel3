<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

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
