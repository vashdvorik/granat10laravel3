<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class FavoritesService
{
    public const PER_PAGE = 12;
    public const MAX_IDS = 200;

    public function getInitialProducts(string $rawIds): array
    {
        $ids = $this->parseIds($rawIds);

        if (empty($ids)) {
            return [
                'products' => collect(),
                'has_more' => false,
                'total' => 0,
                'last_page' => 1,
            ];
        }

        $total = count($ids);

        return [
            'products' => $this->getVisibleProductsByIds(array_slice($ids, 0, self::PER_PAGE), $ids),
            'has_more' => $total > self::PER_PAGE,
            'total' => $total,
            'last_page' => (int) ceil($total / self::PER_PAGE),
        ];
    }

    public function getProductsPage(string $rawIds, int $page): Collection
    {
        $ids = $this->parseIds($rawIds);

        if (empty($ids)) {
            return collect();
        }

        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;
        $pageIds = array_slice($ids, $offset, self::PER_PAGE);

        return $this->getVisibleProductsByIds($pageIds, $ids);
    }

    public function parseIds(string $rawIds): array
    {
        $ids = array_filter(array_map('intval', explode(',', $rawIds)));

        return array_slice(array_values($ids), 0, self::MAX_IDS);
    }

    private function getVisibleProductsByIds(array $pageIds, array $sortIds): Collection
    {
        if (empty($pageIds)) {
            return collect();
        }

        return Product::visible()
            ->forRegionalContext()
            ->whereIn('id', $pageIds)
            ->with(['images', 'seller'])
            ->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $sortIds, true))
            ->values();
    }
}
