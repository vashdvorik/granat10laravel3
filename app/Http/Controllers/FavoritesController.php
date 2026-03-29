<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class FavoritesController extends Controller
{
    private const PER_PAGE = 12;
    private const MAX_IDS  = 200;

    public function index()
    {
        return view('favorites');
    }

    /**
     * Первичная загрузка: возвращает первые PER_PAGE товаров + мета для пагинации.
     */
    public function getProducts(Request $request)
    {
        $ids = $this->parseIds($request->input('ids', ''));

        if (empty($ids)) {
            return response()->json(['html' => '', 'has_more' => false, 'total' => 0, 'last_page' => 1]);
        }

        $total     = count($ids);
        $pageIds   = array_slice($ids, 0, self::PER_PAGE);
        $hasMore   = $total > self::PER_PAGE;
        $lastPage  = (int) ceil($total / self::PER_PAGE);

        $products = Product::visible()
            ->whereIn('id', $pageIds)
            ->with(['images', 'seller'])
            ->get();

        $html = view('components.products-load-more', compact('products'))->render();

        return response()->json([
            'html'      => $html,
            'has_more'  => $hasMore,
            'total'     => $total,
            'last_page' => $lastPage,
        ]);
    }

    /**
     * AJAX load-more: возвращает следующую порцию товаров.
     */
    public function loadMoreFavorites(Request $request)
    {
        $ids  = $this->parseIds($request->input('ids', ''));
        $page = max(1, (int) $request->input('page', 2));

        if (empty($ids)) {
            return response('');
        }

        $offset  = ($page - 1) * self::PER_PAGE;
        $pageIds = array_slice($ids, $offset, self::PER_PAGE);

        $products = Product::visible()
            ->whereIn('id', $pageIds)
            ->with(['images', 'seller'])
            ->get();

        return view('components.products-load-more', compact('products'))->render();
    }

    // -----------------------------------------------------------------------

    private function parseIds(string $raw): array
    {
        $ids = array_filter(array_map('intval', explode(',', $raw)));
        return array_slice(array_values($ids), 0, self::MAX_IDS);
    }
}
