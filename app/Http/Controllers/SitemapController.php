<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use Illuminate\Http\Response;

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

        if (config('regionality.urls_enabled')) {
            Settlement::query()
                ->active()
                ->whereHas('region', fn ($query) => $query->where('is_active', true))
                ->each(function (Settlement $settlement) use ($urls): void {
                    $parameters = ['location' => $settlement->slug];

                    $urls->push(
                        route('regional.home', $parameters),
                        route('regional.catalog.index', $parameters),
                        route('regional.shops', $parameters),
                        route('regional.catalog.sale', $parameters),
                    );

                    Seller::query()
                        ->forRegionalContext($this->contextFor($settlement))
                        ->whereHas('products', fn ($query) => $query->visible())
                        ->pluck('alias')
                        ->each(fn (string $alias) => $urls->push(route(
                            'regional.seller',
                            [...$parameters, 'alias' => $alias]
                        )));

                    Product::query()
                        ->visible()
                        ->forRegionalContext($this->contextFor($settlement))
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

    private function contextFor(Settlement $settlement): \App\Services\RegionalContext
    {
        $context = new \App\Services\RegionalContext();
        $context->setSettlement($settlement);

        return $context;
    }
}
