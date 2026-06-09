<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
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
        $settlement = $this->context->settlement();
        $location = $settlement?->name;
        $suffix = ' | ГРАНАТ';

        if ($request->routeIs('regional.home') && $settlement?->meta_title) {
            return $settlement->meta_title;
        }

        return match (true) {
            $request->routeIs('regional.catalog.show', 'catalog.show') =>
                (($this->categoryName($request) ?: 'Каталог').' — товары'.($location ? ' в '.$location : '')).$suffix,
            $request->routeIs('regional.product.show', 'product.show') =>
                (($this->productName($request) ?: 'Товар').($location ? ' в '.$location : '')).$suffix,
            $request->routeIs('regional.seller', 'seller') =>
                (($this->sellerName($request) ?: 'Магазин').($location ? ' в '.$location : '')).$suffix,
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
        $settlement = $this->context->settlement();
        $location = $settlement?->name ?? 'ПМР';

        if ($request->routeIs('regional.home') && $settlement?->meta_description) {
            return $settlement->meta_description;
        }

        return match (true) {
            $request->routeIs('regional.product.show', 'product.show') =>
                ($this->productName($request) ?: 'Товар')." от местного продавца в {$location}: цена, описание и контакты магазина.",
            $request->routeIs('regional.seller', 'seller') =>
                ($this->sellerName($request) ?: 'Магазин')." в {$location}: товары, цены и контактная информация продавца.",
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

    private function categoryName(Request $request): ?string
    {
        return Category::query()
            ->where('alias', $request->route('alias'))
            ->value('name');
    }

    private function productName(Request $request): ?string
    {
        return Product::query()
            ->where('alias', $request->route('alias'))
            ->value('name');
    }

    private function sellerName(Request $request): ?string
    {
        return Seller::query()
            ->where('alias', $request->route('alias'))
            ->value('name');
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
