<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Services\RegionalContext;

// home() - Отображает главную страницу сайта
class HomeController extends Controller
{
    public function home(RegionalContext $regionalContext)
    {
        $productScope = static fn ($query) => $query
            ->visible()
            ->forRegionalContext();

        $featuredCategories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', $productScope)
            ->withCount(['products as visible_products_count' => $productScope])
            ->orderByDesc('visible_products_count')
            ->orderBy('number')
            ->limit(10)
            ->get();

        $latestProducts = Product::visible()
            ->forRegionalContext()
            ->with(['images', 'seller'])
            ->orderByDesc('is_vip')
            ->orderByDesc('push_at')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $saleProducts = Product::visible()
            ->forRegionalContext()
            ->with(['images', 'seller'])
            ->whereNotNull('lowprice')
            ->where('lowprice', '>', 0)
            ->whereColumn('lowprice', '<', 'price')
            ->orderByRaw('(price - lowprice) / NULLIF(price, 0) DESC')
            ->limit(6)
            ->get();

        $featuredSellers = Seller::forRegionalContext()
            ->whereHas('products', fn ($query) => $query->visible())
            ->withCount(['products as visible_products_count' => fn ($query) => $query->visible()])
            ->orderByDesc('visible_products_count')
            ->limit(6)
            ->get();
        $visibleSellersCount = Seller::forRegionalContext()
            ->whereHas('products', fn ($query) => $query->visible())
            ->count();

        return view('home', [
            'featuredCategories' => $featuredCategories,
            'latestProducts' => $latestProducts,
            'saleProducts' => $saleProducts,
            'featuredSellers' => $featuredSellers,
            'locationName' => $regionalContext->settlement()?->name ?? 'ПМР',
            'visibleProductsCount' => Product::visible()->forRegionalContext()->count(),
            'visibleSellersCount' => $visibleSellersCount,
        ]);
    }
}
