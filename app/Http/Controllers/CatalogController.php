<?php

namespace App\Http\Controllers;

use App\Services\CatalogPageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function catalog(Request $request, CatalogPageService $catalogPageService): View
    {
        return view('catalog.index', $catalogPageService->getCatalogPageData($request));
    }

    public function show(string $alias, Request $request, CatalogPageService $catalogPageService): View
    {
        $category = $catalogPageService->findActiveCategoryByAlias($alias);

        return view('catalog.show', $catalogPageService->getCategoryPageData($category, $request));
    }

    public function sale(CatalogPageService $catalogPageService): View
    {
        return view('salepage', [
            'products' => $catalogPageService->getSaleProducts(),
        ]);
    }

    public function loadMoreCatalog(Request $request, CatalogPageService $catalogPageService): string
    {
        $products = $catalogPageService->getCatalogProducts($request);

        return view('components.products-load-more', compact('products'))->render();
    }

    public function loadMoreCategory(string $alias, Request $request, CatalogPageService $catalogPageService): string
    {
        $category = $catalogPageService->findActiveCategoryByAlias($alias);
        $products = $catalogPageService->getCatalogProducts(
            $request,
            $catalogPageService->getCategoryIds($category)
        );

        return view('components.products-load-more', compact('products'))->render();
    }

    public function loadMoreSale(Request $request, CatalogPageService $catalogPageService): string
    {
        $products = $catalogPageService->getSaleProducts();

        return view('components.products-load-more', compact('products'))->render();
    }

    public function search(Request $request, CatalogPageService $catalogPageService): View
    {
        return view('search', $catalogPageService->getSearchPageData($request));
    }

    public function loadMoreSearch(Request $request, CatalogPageService $catalogPageService): string
    {
        $q = trim($request->input('q', ''));
        $categoryAlias = $request->input('category', '');
        $products = $catalogPageService->getSearchProducts($q, $categoryAlias);

        return view('components.products-load-more', compact('products'))->render();
    }
}
