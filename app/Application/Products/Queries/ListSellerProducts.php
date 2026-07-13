<?php

namespace App\Application\Products\Queries;

use App\Enums\ProductModerationStatus;
use App\Models\Category;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ListSellerProducts
{
    private const STATUS_FILTERS = [
        'all' => 'Все',
        'visible' => 'Видны покупателям',
        'hidden' => 'Скрыты',
        'draft' => 'Черновики',
        'moderation' => 'На проверке',
        'fixes' => 'Нужно исправить',
        'no_images' => 'Без фото',
        'tariff' => 'Скрыты из-за тарифа',
        'vip' => 'VIP',
    ];

    public function execute(
        Seller $seller,
        ?int $categoryId = null,
        array $queryParameters = [],
        int $perPage = 20
    ): array {
        $query = $seller->products()->with(['category', 'images', 'seller']);
        $status = $this->normalizeStatus($queryParameters['product_status'] ?? null);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        $this->applyStatusFilter($query, $seller, $status);

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
            'productStatusFilters' => $this->statusFilters($seller),
            'currentProductStatus' => $status,
        ];
    }

    private function normalizeStatus(mixed $status): string
    {
        return is_string($status) && array_key_exists($status, self::STATUS_FILTERS)
            ? $status
            : 'all';
    }

    private function applyStatusFilter(Builder|HasMany $query, Seller $seller, string $status): void
    {
        match ($status) {
            'visible' => $this->whereVisibleToBuyers($query),
            'hidden' => $this->whereHiddenFromBuyers($query),
            'draft' => $query->where('moderation_status', ProductModerationStatus::Draft->value),
            'moderation' => $query->whereIn('moderation_status', [
                ProductModerationStatus::Pending->value,
                ProductModerationStatus::Processing->value,
                ProductModerationStatus::NeedsReview->value,
                ProductModerationStatus::Failed->value,
            ]),
            'fixes' => $query->whereIn('moderation_status', [
                ProductModerationStatus::ChangesRequired->value,
                ProductModerationStatus::Rejected->value,
            ]),
            'no_images' => $query->whereDoesntHave('images'),
            'tariff' => $this->whereHiddenByTariff($query, $seller),
            'vip' => $query->where('is_vip', true),
            default => null,
        };
    }

    private function whereVisibleToBuyers(Builder|HasMany $query): void
    {
        $query
            ->where('is_active', true)
            ->where('moderation_status', ProductModerationStatus::Approved->value)
            ->whereColumn('moderated_version', 'moderation_version')
            ->whereHas('images')
            ->whereHas('seller.sellerPackages', fn (Builder $sellerPackageQuery) => $this->activePackageConstraint($sellerPackageQuery));
    }

    private function whereHiddenFromBuyers(Builder|HasMany $query): void
    {
        $query->where(function (Builder $hiddenQuery): void {
            $hiddenQuery
                ->where('is_active', false)
                ->orWhere('moderation_status', '!=', ProductModerationStatus::Approved->value)
                ->orWhereNull('moderated_version')
                ->orWhereColumn('moderated_version', '!=', 'moderation_version')
                ->orWhereDoesntHave('images')
                ->orWhereDoesntHave('seller.sellerPackages', fn (Builder $sellerPackageQuery) => $this->activePackageConstraint($sellerPackageQuery));
        });
    }

    private function whereHiddenByTariff(Builder|HasMany $query, Seller $seller): void
    {
        $activePackage = $seller->activePackage();

        if ($activePackage === null) {
            $query->where('is_active', true);

            return;
        }

        $package = $activePackage->package;
        $activeProductsLimit = $package?->active_products_limit;
        $vipProductsLimit = $package?->vip_products_limit;
        $activeProductsCount = $seller->products()->where('is_active', true)->count();
        $vipProductsCount = $seller->products()
            ->where('is_active', true)
            ->where('is_vip', true)
            ->count();
        $activeLimitExceeded = $activeProductsLimit !== null && $activeProductsCount > $activeProductsLimit;
        $vipLimitExceeded = $vipProductsLimit !== null && $vipProductsCount > $vipProductsLimit;

        if (! $activeLimitExceeded && ! $vipLimitExceeded) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $tariffQuery) use ($activeLimitExceeded, $vipLimitExceeded): void {
            if ($activeLimitExceeded) {
                $tariffQuery->orWhere('is_active', true);
            }

            if ($vipLimitExceeded) {
                $tariffQuery->orWhere(fn (Builder $vipQuery) => $vipQuery
                    ->where('is_active', true)
                    ->where('is_vip', true));
            }
        });
    }

    private function statusFilters(Seller $seller): array
    {
        return collect(self::STATUS_FILTERS)
            ->map(fn (string $label, string $status): array => [
                'key' => $status,
                'label' => $label,
                'count' => $this->countForStatus($seller, $status),
            ])
            ->values()
            ->all();
    }

    private function countForStatus(Seller $seller, string $status): int
    {
        $query = $seller->products()->getQuery();
        $this->applyStatusFilter($query, $seller, $status);

        return $query->count();
    }

    private function activePackageConstraint(Builder $query): void
    {
        $query
            ->where('is_active', true)
            ->where(function (Builder $dateQuery): void {
                $dateQuery
                    ->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            });
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
