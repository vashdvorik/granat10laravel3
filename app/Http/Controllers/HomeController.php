<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Services\HomeFeedService;
use App\Services\RegionalContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// home() - Отображает главную страницу сайта
class HomeController extends Controller
{
    public function home(RegionalContext $regionalContext, HomeFeedService $feed)
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

        $popularSearchCategories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', $productScope)
            ->withCount([
                'products as visible_products_count' => $productScope,
                'products as recent_visible_products_count' => static fn ($query) => $query
                    ->visible()
                    ->forRegionalContext()
                    ->where('products.created_at', '>=', now()->subDays(30)),
            ])
            ->orderByDesc('recent_visible_products_count')
            ->orderByDesc('visible_products_count')
            ->orderBy('number')
            ->limit(24)
            ->get()
            ->shuffle()
            ->take(9)
            ->values();

        $latestProducts = $feed->latest();

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
            'popularSearchCategories' => $popularSearchCategories,
            'latestProducts' => $latestProducts,
            'feedChips' => $feed->chips(),
            'saleProducts' => $saleProducts,
            'featuredSellers' => $featuredSellers,
            'locationName' => $regionalContext->settlement()?->name ?? 'ПМР',
            'visibleProductsCount' => Product::visible()->forRegionalContext()->count(),
            'visibleSellersCount' => $visibleSellersCount,
        ]);
    }

    /**
     * Карточки новинок для быстрых фильтров над лентой на главной: без параметра — вся лента, с `category` — одна
     * корневая категория. Возвращает готовый HTML карточек. Неизвестная или пустая категория — 404.
     */
    public function latest(Request $request, HomeFeedService $feed): Response
    {
        $alias = trim((string) $request->query('category', ''));
        $root = null;

        if ($alias !== '') {
            $root = $feed->chipCategory($alias);
            abort_unless($root, 404);
        }

        $products = $feed->latest($root);

        $html = $products->isEmpty()
            ? '<p class="home-feed-empty">Пока нет новинок. Загляните в каталог.</p>'
            : view('components.products-load-more', ['products' => $products])->render();

        return response($html)->header('Cache-Control', 'no-store');
    }
}
