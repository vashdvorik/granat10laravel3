<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

/* show($alias) - Показывает детальную страницу товара по alias,
   загружает связанные данные (изображения, продавца, категорию), 
   формирует хлебные крошки для навигации. */

class ProductController extends Controller
{
       public function show($alias)
    {
        $product = Product::visible()  
        ->with(['images', 'seller', 'category'])
        ->where('alias', $alias)
        ->firstOrFail();

        // Считаем просмотр
        DB::table('views')->updateOrInsert(
            ['product_id' => $product->id],
            ['views' => DB::raw('views + 1')]
        );

        // Получаем категорию товара
        $category = $product->category;

        // Получаем хлебные крошки для категории товара
        $breadcrumbs = $category ? $category->breadcrumbs : [];
        
        return view('product', compact('product', 'breadcrumbs', 'category'));
    }


}
