<?php

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
        $updated = DB::table('views')
            ->where('product_id', $product->id)
            ->increment('views');

        if ($updated === 0) {
            DB::table('views')->insert([
                'product_id' => $product->id,
                'views' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Получаем категорию товара
        $category = $product->category;

        // Получаем хлебные крошки для категории товара
        $breadcrumbs = $category ? $category->breadcrumbs : [];
        
        return view('product', compact('product', 'breadcrumbs', 'category'));
    }


}
