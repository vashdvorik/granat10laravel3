<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LegacySeoMetadataService
{
    public function __construct(
        private readonly SeoService $seo,
        private readonly RegionalContext $context,
    ) {
    }

    public function canonical(Request $request): string
    {
        return $this->seo->canonical($request);
    }

    public function robots(Request $request): string
    {
        return $this->seo->robots($request);
    }

    public function title(Request $request): string
    {
        return $this->seo->title($request);
    }

    public function description(Request $request): string
    {
        $baseDescription = $this->seo->description($request);
        $location = $this->context->settlement()?->name;

        if (! $location) {
            $location = 'ПМР';
        }

        $model = match (true) {
            $request->routeIs('regional.product.show', 'product.show') => Product::query()
                ->where('alias', $request->route('alias'))
                ->first(['name', 'desc']),
            $request->routeIs('regional.catalog.show', 'catalog.show') => Category::query()
                ->where('alias', $request->route('alias'))
                ->first(['name', 'desc']),
            $request->routeIs('regional.seller', 'seller') => Seller::query()
                ->where('alias', $request->route('alias'))
                ->first(['name', 'description']),
            default => null,
        };

        if (! $model) {
            return $baseDescription;
        }

        $description = $model instanceof Seller ? $model->description : $model->desc;
        $description = $this->plainText($description);

        if ($description === '') {
            return $baseDescription;
        }

        return Str::limit(
            sprintf('%s в %s: %s', $model->name, $location, $description),
            160,
            '…',
        );
    }

    private function plainText(?string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($value ?? '')));
    }
}
