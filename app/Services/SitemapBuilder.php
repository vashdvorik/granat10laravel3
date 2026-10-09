<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use Illuminate\Support\Facades\Cache;

/**
 * Список страниц для sitemap.xml: только то, что можно показывать в поиске, и с датой последнего изменения.
 *
 * Правила:
 * - товар и магазин указаны по одному адресу — по адресу города продавца (как canonical на самой странице);
 * - товары и магазины без города (или в отключённом городе) идут по общему адресу, чтобы не пропасть из карты;
 * - пустые города и пустые категории в карту не попадают.
 */
class SitemapBuilder
{
    /**
     * @return array<int, array{loc: string, lastmod: ?string}>
     */
    public function entries(): array
    {
        $ttl = (int) config('seo.sitemap_ttl', 900);

        if ($ttl <= 0) {
            return $this->build();
        }

        return Cache::remember('seo:sitemap:entries', $ttl, fn (): array => $this->build());
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string}>
     */
    private function build(): array
    {
        $entries = [];
        $add = function (string $loc, mixed $lastmod = null) use (&$entries): void {
            $entries[$loc] ??= ['loc' => $loc, 'lastmod' => $lastmod ? $lastmod->toAtomString() : null];
        };

        $regional = (bool) config('regionality.urls_enabled');

        foreach ([route('home'), route('catalog.index'), route('shops'), route('catalog.sale')] as $url) {
            $add($url);
        }

        $this->addCategories($add, null, null);

        $attachedSellerIds = collect();

        if ($regional) {
            Settlement::query()
                ->active()
                ->whereHas('region', fn ($query) => $query->where('is_active', true))
                ->each(function (Settlement $settlement) use ($add, &$attachedSellerIds): void {
                    $context = new RegionalContext();
                    $context->setSettlement($settlement);

                    $products = Product::query()->visible()->forRegionalContext($context);

                    // пустой город в поиск не отдаём
                    if (! (clone $products)->exists()) {
                        return;
                    }

                    $parameters = ['location' => $settlement->slug];

                    foreach (['regional.home', 'regional.catalog.index', 'regional.shops', 'regional.catalog.sale'] as $name) {
                        $add(route($name, $parameters));
                    }

                    $this->addCategories($add, $context, $settlement);

                    Seller::query()
                        ->forRegionalContext($context)
                        ->whereHas('products', fn ($query) => $query->visible())
                        ->get(['id', 'alias', 'updated_at'])
                        ->each(function (Seller $seller) use ($add, $parameters, &$attachedSellerIds): void {
                            $attachedSellerIds->push($seller->id);
                            $add(route('regional.seller', [...$parameters, 'alias' => $seller->alias]), $seller->updated_at);
                        });

                    (clone $products)
                        ->get(['alias', 'updated_at'])
                        ->each(fn (Product $product) => $add(
                            route('regional.product.show', [...$parameters, 'alias' => $product->alias]),
                            $product->updated_at
                        ));
                });
        }

        // магазины и товары без действующего города (или при выключенных городских адресах) — по общему адресу
        $attached = $attachedSellerIds->all();

        Seller::query()
            ->whereHas('products', fn ($query) => $query->visible())
            ->when($attached !== [], fn ($query) => $query->whereNotIn('id', $attached))
            ->get(['id', 'alias', 'updated_at'])
            ->each(function (Seller $seller) use ($add): void {
                $add(route('seller', ['alias' => $seller->alias]), $seller->updated_at);

                Product::query()
                    ->visible()
                    ->where('seller_id', $seller->id)
                    ->get(['alias', 'updated_at'])
                    ->each(fn (Product $product) => $add(route('product.show', ['alias' => $product->alias]), $product->updated_at));
            });

        return array_values($entries);
    }

    private function addCategories(callable $add, ?RegionalContext $context, ?Settlement $settlement): void
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->defaultOrder()
            ->get(['id', 'alias', '_lft', '_rgt', 'updated_at']);

        $usedCategoryIds = Product::query()
            ->visible()
            ->when($context, fn ($query) => $query->forRegionalContext($context))
            ->distinct()
            ->pluck('category_id');

        if ($usedCategoryIds->isEmpty()) {
            return;
        }

        $usedCategories = $categories->whereIn('id', $usedCategoryIds);

        foreach ($categories as $category) {
            $hasProducts = $usedCategories->contains(
                fn (Category $used): bool => $category->_lft <= $used->_lft && $category->_rgt >= $used->_rgt
            );

            if (! $hasProducts) {
                continue;
            }

            $add(
                $settlement
                    ? route('regional.catalog.show', ['location' => $settlement->slug, 'alias' => $category->alias])
                    : route('catalog.show', ['alias' => $category->alias]),
                $category->updated_at
            );
        }
    }
}
