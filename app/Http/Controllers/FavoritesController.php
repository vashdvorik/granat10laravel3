<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\FavoritesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FavoritesController extends Controller
{
    public function index(): View
    {
        $recommendedProducts = Product::visible()
            ->forRegionalContext()
            ->with(['images', 'seller'])
            ->orderByDesc('is_vip')
            ->orderByDesc('push_at')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('favorites', compact('recommendedProducts'));
    }

    public function getProducts(Request $request, FavoritesService $favoritesService): JsonResponse
    {
        $result = $favoritesService->getInitialProducts($request->input('ids', ''));
        $html = view('components.products-load-more', ['products' => $result['products']])->render();

        return response()->json([
            'html' => $html,
            'has_more' => $result['has_more'],
            'total' => $result['total'],
            'last_page' => $result['last_page'],
        ]);
    }

    public function loadMoreFavorites(Request $request, FavoritesService $favoritesService): Response|string
    {
        $products = $favoritesService->getProductsPage(
            $request->input('ids', ''),
            (int) $request->input('page', 2)
        );

        if ($products->isEmpty()) {
            return response('');
        }

        return view('components.products-load-more', compact('products'))->render();
    }
}
