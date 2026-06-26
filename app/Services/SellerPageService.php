<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SellerPageService
{
    public function getShops(int $perPage = 12): LengthAwarePaginator
    {
        return Seller::forRegionalContext()
            ->with('settlement')
            ->withCount(['products' => function ($query) {
                $query->where('is_active', true);
            }])
            ->orderBy('products_count', 'desc')
            ->paginate($perPage);
    }

    public function getSellerProducts(Seller $seller, int $perPage = 12): LengthAwarePaginator
    {
        return Product::visible()
            ->forRegionalContext()
            ->where('seller_id', $seller->id)
            ->with(['images', 'seller'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getSellerCategories(Seller $seller): Collection
    {
        $categories = Category::getCachedRootCategories();

        return $categories->filter(function (Category $category) use ($seller) {
            $totalProducts = $this->countSellerProductsInCategory($category, $seller);
            $category->total_products = $totalProducts;

            return $totalProducts > 0;
        });
    }

    public function getSellerPageData(Seller $seller): array
    {
        return [
            'seller' => $seller,
            'categories' => $this->getSellerCategories($seller),
            'products' => $this->getSellerProducts($seller),
        ];
    }

    private function countSellerProductsInCategory(Category $category, Seller $seller): int
    {
        $count = $category->products()
            ->where('seller_id', $seller->id)
            ->count();

        foreach ($category->children as $child) {
            $count += $this->countSellerProductsInCategory($child, $seller);
        }

        return $count;
    }
}
