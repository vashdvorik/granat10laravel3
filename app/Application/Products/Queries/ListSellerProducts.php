<?php

namespace App\Application\Products\Queries;

use App\Models\Category;
use App\Models\Seller;
use Illuminate\Support\Collection;

class ListSellerProducts
{
    public function execute(
        Seller $seller,
        ?int $categoryId = null,
        array $queryParameters = [],
        int $perPage = 20
    ): array {
        $query = $seller->products()->with(['category', 'images']);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderByDesc('created_at')
            ->paginate($perPage)
            ->appends($queryParameters);

        $sellerCategoryIds = $seller->products()
            ->distinct()
            ->pluck('category_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return [
            'products' => $products,
            'categoryTree' => $this->categoryTree($sellerCategoryIds),
            'sellerCategoryIds' => $sellerCategoryIds,
        ];
    }

    private function categoryTree(array $sellerCategoryIds): Collection
    {
        if ($sellerCategoryIds === []) {
            return collect();
        }

        $categories = Category::query()->whereIn('id', $sellerCategoryIds)->get();
        $allCategoryIds = $categories->pluck('id');

        foreach ($categories as $category) {
            $allCategoryIds = $allCategoryIds->merge($category->ancestors()->pluck('id'));
        }

        return Category::query()
            ->whereIn('id', $allCategoryIds->unique()->values())
            ->where('is_active', true)
            ->defaultOrder()
            ->get()
            ->toTree();
    }
}
