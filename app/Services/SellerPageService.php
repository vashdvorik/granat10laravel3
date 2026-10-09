<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SellerPageService
{
    public function getShops(int $perPage = 12): LengthAwarePaginator
    {
        return Seller::forRegionalContext()
            ->publiclyVisible()
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

    /**
     * Корневые категории, в которых у магазина есть товары, и число видимых товаров в каждой (с подкатегориями).
     *
     * Раньше счёт шёл рекурсивно, по два запроса на каждую категорию дерева: на настоящем каталоге
     * (1600 категорий) это было более 3000 запросов и секунда работы на каждый просмотр магазина.
     * Теперь запросов два: сколько товаров в каждой категории и где эти категории стоят в дереве.
     */
    public function getSellerCategories(Seller $seller): Collection
    {
        $counts = Product::visible()
            ->forRegionalContext()
            ->where('seller_id', $seller->id)
            ->select('category_id', DB::raw('count(*) as aggregate'))
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        if ($counts->isEmpty()) {
            return new Collection();
        }

        // слева направо по дереву: потомки корня лежат внутри его границ _lft.._rgt
        $lefts = Category::query()
            ->whereIn('id', $counts->keys())
            ->get(['id', '_lft'])
            ->mapWithKeys(fn (Category $category): array => [$category->id => (int) $category->_lft]);

        return Category::getCachedRootCategories()
            ->filter(function (Category $root) use ($counts, $lefts): bool {
                $total = 0;

                foreach ($lefts as $id => $left) {
                    if ($left >= (int) $root->_lft && $left <= (int) $root->_rgt) {
                        $total += (int) $counts[$id];
                    }
                }

                $root->total_products = $total;

                return $total > 0;
            })
            ->values();
    }

    public function getSellerPageData(Seller $seller): array
    {
        return [
            'seller' => $seller,
            'categories' => $this->getSellerCategories($seller),
            'products' => $this->getSellerProducts($seller),
        ];
    }
}
