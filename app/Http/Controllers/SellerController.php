<?php

namespace App\Http\Controllers;

use App\Models\Seller;
use App\Services\SellerPageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerController extends Controller
{
    public function shops(SellerPageService $sellerPageService): View
    {
        $sellers = $sellerPageService->getShops();

        return view('sellers', compact('sellers'));
    }

    public function seller(string $alias, SellerPageService $sellerPageService): View
    {
        $seller = Seller::getCachedByAlias($alias);

        if (!$seller) {
            abort(404);
        }

        return view('seller', $sellerPageService->getSellerPageData($seller));
    }

    public function loadMoreSellerProducts(string $alias, Request $request, SellerPageService $sellerPageService)
    {
        $seller = Seller::getCachedByAlias($alias);

        if (!$seller) {
            abort(404);
        }

        $products = $sellerPageService->getSellerProducts($seller);

        return view('components.seller-products-load-more', compact('products'))->render();
    }

    public function loadMoreShops(Request $request, SellerPageService $sellerPageService)
    {
        $sellers = $sellerPageService->getShops();

        return view('components.sellers-load-more', compact('sellers'))->render();
    }
}
