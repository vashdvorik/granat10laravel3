<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use App\Services\RegionalContext;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            route('home'),
            route('catalog.index'),
            route('shops'),
            route('catalog.sale'),
        ]);

        $this->appendCategoryUrls($urls);

        if (config('regionality.urls_enabled')) {
            Settlement::query()
                ->active()
                ->whereHas('region', fn ($query) => $query->where('is_active', true))
                ->each(function (Settlement $settlement) use ($urls): void {
                    $parameters = ['location' => $settlement->slug];
                    $context = $this->contextFor($settlement);

                    $urls->push(
                        route('regional.home', $parameters),
                        route('regional.catalog.index', $parameters),
                        route('regional.shops', $parameters),
                        route('regional.catalog.sale', $parameters),
                    );

                    $this->appendCategoryUrls($urls, $context, $settlement);

                    Seller::query()
                        ->forRegionalContext($context)
                        ->whereHas('products', fn ($query) => $query->visible())
                        ->pluck('alias')
                        ->each(fn (string $alias) => $urls->push(route(
                            'regional.seller',
                            [...$parameters, 'alias' => $alias]
                        )));

                    Product::query()
                        ->visible()
                        ->forRegionalContext($context)
                        ->pluck('alias')
                        ->each(fn (string $alias) => $urls->push(route(
                            'regional.product.show',
                            [...$parameters, 'alias' => $alias]
                        )));
                });
        }

        return response()
            ->view('sitemap', ['urls' => $urls->unique()->values()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function appendCategoryUrls(
        Collection $urls,
        ?RegionalContext $context = null,
        ?Settlement $settlement = null
    ): void {
        $categories = Category::query()
            ->where('is_active', true)
            ->defaultOrder()
            ->get(['id', 'alias', '_lft', '_rgt']);

        $usedCategoryIds = Product::query()
            ->visible()
            ->when($context, fn ($query) => $query->forRegionalContext($context))
            ->distinct()
            ->pluck('category_id');

        if ($usedCategoryIds->isEmpty()) {
            return;
        }

        $usedCategories = $categories->whereIn('id', $usedCategoryIds);
        $availableCategories = $categories->filter(
            fn (Category $category): bool => $usedCategories->contains(
                fn (Category $used): bool => $category->_lft <= $used->_lft
                    && $category->_rgt >= $used->_rgt
            )
        );

        foreach ($availableCategories as $category) {
            $urls->push($settlement
                ? route('regional.catalog.show', [
                    'location' => $settlement->slug,
                    'alias' => $category->alias,
                ])
                : route('catalog.show', ['alias' => $category->alias]));
        }
    }

    private function contextFor(Settlement $settlement): RegionalContext
    {
        $context = new RegionalContext();
        $context->setSettlement($settlement);

        return $context;
    }
}
