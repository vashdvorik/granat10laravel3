<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\OrderAvailabilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/* show($alias) - Показывает детальную страницу товара по alias,
   загружает связанные данные (изображения, продавца, категорию),
   формирует хлебные крошки для навигации. */

class ProductController extends Controller
{
    public function show($alias, OrderAvailabilityService $orderAvailabilityService)
    {
        $product = Product::visible()
            ->forRegionalContext()
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

        $orderAvailability = $orderAvailabilityService->check($product);
        $orderIdempotencyKey = (string) Str::uuid();
        $relatedProducts = Product::visible()
            ->forRegionalContext()
            ->with(['images', 'seller'])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->getKey())
            ->orderByVipFirst()
            ->orderByDesc('push_at')
            ->limit(8)
            ->get();
        $relatedTitle = 'Похожие товары';
        $relatedFromSeller = false;

        if ($relatedProducts->isEmpty()) {
            $relatedProducts = Product::visible()
                ->forRegionalContext()
                ->with(['images', 'seller'])
                ->where('seller_id', $product->seller_id)
                ->whereKeyNot($product->getKey())
                ->orderByVipFirst()
                ->orderByDesc('push_at')
                ->limit(8)
                ->get();
            $relatedTitle = 'Другие товары продавца';
            $relatedFromSeller = true;
        }

        return view('product', compact(
            'product',
            'breadcrumbs',
            'category',
            'orderAvailability',
            'orderIdempotencyKey',
            'relatedProducts',
            'relatedTitle',
            'relatedFromSeller',
        ));
    }
}
