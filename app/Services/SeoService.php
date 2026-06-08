<?php

namespace App\Services;

use Illuminate\Http\Request;

class SeoService
{
    public function __construct(private readonly RegionalContext $context)
    {
    }

    public function canonical(Request $request): string
    {
        return $request->url();
    }

    public function robots(Request $request): string
    {
        if ($this->isTechnicalPage($request) || $this->hasCatalogFilters($request)) {
            return 'noindex, follow';
        }

        return 'index, follow';
    }

    public function title(Request $request): string
    {
        $location = $this->context->settlement()?->name;
        $suffix = ' | ГРАНАТ';

        return match (true) {
            $request->routeIs('regional.catalog.show', 'catalog.show') =>
                (($request->route('alias') ?: 'Каталог').' — товары'.($location ? ' в '.$location : '')).$suffix,
            $request->routeIs('regional.catalog.index', 'catalog.index') =>
                ('Каталог товаров'.($location ? ' в '.$location : ' в ПМР')).$suffix,
            $request->routeIs('regional.shops', 'shops') =>
                ('Магазины'.($location ? ' в '.$location : ' ПМР')).$suffix,
            $request->routeIs('regional.catalog.sale', 'catalog.sale') =>
                ('Распродажи и акции'.($location ? ' в '.$location : ' в ПМР')).$suffix,
            $request->routeIs('regional.home', 'home') =>
                ('Товары и магазины'.($location ? ' в '.$location : ' в ПМР')).$suffix,
            default => '',
        };
    }

    public function description(Request $request): string
    {
        $location = $this->context->settlement()?->name ?? 'ПМР';

        return match (true) {
            $request->routeIs('regional.shops', 'shops') =>
                "Каталог магазинов и местных продавцов в {$location}.",
            $request->routeIs('regional.catalog.sale', 'catalog.sale') =>
                "Актуальные скидки, акции и распродажи магазинов в {$location}.",
            $request->routeIs('regional.catalog.*', 'catalog.*') =>
                "Товары местных магазинов в {$location}: цены, категории и предложения продавцов.",
            $request->routeIs('regional.home', 'home') =>
                "Маркетплейс товаров и магазинов в {$location}.",
            default => '',
        };
    }

    private function isTechnicalPage(Request $request): bool
    {
        return $request->routeIs(
            'search',
            'search.loadMore',
            'regional.search',
            'regional.search.loadMore',
            '*.loadMore',
            '*.loadMoreCategory',
            '*.loadMoreSale'
        );
    }

    private function hasCatalogFilters(Request $request): bool
    {
        return $request->hasAny([
            'seller',
            'min_price',
            'max_price',
            'sort',
            'utm_source',
            'utm_medium',
            'utm_campaign',
        ]);
    }
}
