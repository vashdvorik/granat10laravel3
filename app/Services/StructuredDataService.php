<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use App\Support\PageTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Микроразметка schema.org (JSON-LD) публичных страниц — для всех тем оформления одинаково.
 *
 * Что объясняем поисковику: BIMM — местный маркетплейс (Organization + WebSite с поиском), товар продаёт
 * конкретный магазин в конкретном городе (Offer.seller, Offer.availableAtOrFrom), магазин — это локальный
 * бизнес с адресом (Store). В разметку попадают только реальные данные: без выдуманных отзывов и рейтингов.
 */
class StructuredDataService
{
    public function __construct(
        private readonly SeoPageContext $page,
        private readonly RegionalUrlService $regionalUrl,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forRequest(Request $request): array
    {
        $kind = $this->page->kind($request);
        $items = [];

        if ($kind === SeoPageContext::HOME) {
            $items[] = $this->organization($request);
            $items[] = $this->website();
        }

        $breadcrumbs = $this->breadcrumbs($request, $kind);

        if ($breadcrumbs !== null) {
            $items[] = $breadcrumbs;
        }

        if ($kind === SeoPageContext::PRODUCT && ($product = $this->page->product($request))) {
            $items[] = $this->product($product);
        }

        if ($kind === SeoPageContext::SELLER && ($seller = $this->page->seller($request))) {
            $items[] = $this->store($seller, withContext: true);
        }

        return $items;
    }

    // ------------------------------------------------------------------ сайт

    /** @return array<string, mixed> */
    private function organization(Request $request): array
    {
        $settlement = $this->page->settlement($request);

        $organization = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => PageTitle::brand(),
            'url' => url('/'),
            'logo' => asset('img/logo.png'),
            'description' => 'Местный маркетплейс: товары и магазины Приднестровья в одном месте.',
            'areaServed' => $settlement
                ? ['@type' => 'City', 'name' => $settlement->name]
                : ['@type' => 'AdministrativeArea', 'name' => (string) config('seo.area_name', 'Приднестровье')],
        ];

        $sameAs = array_values((array) config('seo.same_as', []));

        if ($sameAs !== []) {
            $organization['sameAs'] = $sameAs;
        }

        return $organization;
    }

    /** @return array<string, mixed> */
    private function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'name' => PageTitle::brand(),
            'url' => url('/'),
            'inLanguage' => 'ru',
            'publisher' => ['@id' => url('/').'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $this->regionalUrl->route('search').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    // ------------------------------------------------------------------ хлебные крошки

    /** @return array<string, mixed>|null */
    private function breadcrumbs(Request $request, string $kind): ?array
    {
        $trail = match ($kind) {
            SeoPageContext::CATALOG => [['Каталог', $this->regionalUrl->route('catalog.index')]],
            SeoPageContext::SHOPS => [['Магазины', $this->regionalUrl->route('shops')]],
            SeoPageContext::SALE => [['Скидки и акции', $this->regionalUrl->route('catalog.sale')]],
            SeoPageContext::CATEGORY => $this->categoryTrail($request),
            SeoPageContext::PRODUCT => $this->productTrail($request),
            SeoPageContext::SELLER => $this->sellerTrail($request),
            default => null,
        };

        if ($trail === null) {
            return null;
        }

        $items = [['Главная', $this->regionalUrl->route('home')], ...$trail];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $item, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item[0],
                'item' => $item[1],
            ], $items, array_keys($items)),
        ];
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function categoryTrail(Request $request): array
    {
        $category = $this->page->category($request);
        $trail = [['Каталог', $this->regionalUrl->route('catalog.index')]];

        if ($category) {
            foreach ($category->breadcrumbs as $crumb) {
                $trail[] = [(string) $crumb->name, $this->regionalUrl->route('catalog.show', ['alias' => $crumb->alias])];
            }
        }

        return $trail;
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function productTrail(Request $request): array
    {
        $product = $this->page->product($request);
        $trail = $this->categoryTrail($request);

        if ($product) {
            $trail[] = [(string) $product->name, $this->canonicalProductUrl($product)];
        }

        return $trail;
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function sellerTrail(Request $request): array
    {
        $seller = $this->page->seller($request);

        return [
            ['Магазины', $this->regionalUrl->route('shops')],
            [(string) ($seller?->name ?? 'Магазин'), $seller ? $this->canonicalSellerUrl($seller) : url('/shops')],
        ];
    }

    // ------------------------------------------------------------------ товар и магазин

    /** @return array<string, mixed> */
    private function product(Product $product): array
    {
        $images = $product->images
            ->sortBy(fn ($image) => sprintf('%d-%08d', $image->is_main ? 0 : 1, $image->order ?? 0))
            ->values();

        $price = $product->lowprice !== null && (float) $product->lowprice > 0 && (float) $product->lowprice < (float) $product->price
            ? (float) $product->lowprice
            : (float) $product->price;

        $url = $this->canonicalProductUrl($product);

        $offer = [
            '@type' => 'Offer',
            'url' => $url,
            'priceCurrency' => 'RUB',
            'price' => number_format($price, 2, '.', ''),
            'availability' => 'https://schema.org/InStock',
        ];

        if ($product->seller) {
            $store = $this->store($product->seller, withContext: false);
            $offer['seller'] = $store;

            if (isset($store['address'])) {
                // товар можно получить в магазине: именно эту «локальность» поисковик связывает с городом запроса
                $offer['availableAtOrFrom'] = ['@type' => 'Place', 'name' => $store['name'], 'address' => $store['address']];
            }
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'url' => $url,
            'description' => $this->plain($product->desc) ?: (string) $product->name,
            'sku' => (string) $product->id,
            'offers' => $offer,
        ];

        if ($product->category) {
            $schema['category'] = (string) $product->category->name;
        }

        if ($images->isNotEmpty()) {
            $schema['image'] = $images->map(fn ($image) => url(Storage::url($image->path)))->values()->all();
        }

        return $schema;
    }

    /** @return array<string, mixed> */
    private function store(Seller $seller, bool $withContext): array
    {
        $seller->loadMissing('settlement');
        $settlement = $seller->settlement;

        $store = [
            '@type' => 'Store',
            'name' => trim((string) $seller->name, "\"'«» \t\n\r"),
            'url' => $this->canonicalSellerUrl($seller),
        ];

        if ($withContext) {
            $store = ['@context' => 'https://schema.org'] + $store;
        }

        if ($address = $this->address($seller, $settlement)) {
            $store['address'] = $address;
        }

        if ($settlement) {
            $store['areaServed'] = ['@type' => 'City', 'name' => $settlement->name];
        }

        $description = $this->plain($seller->description);

        if ($description !== '') {
            $store['description'] = $description;
        }

        $phone = preg_replace('/[^\d+]/', '', (string) $seller->phone);

        if ($phone !== '') {
            $store['telephone'] = $phone;
        }

        if ($seller->photo) {
            $store['image'] = url(Storage::url($seller->photo));
        }

        if (filter_var($seller->link_map, FILTER_VALIDATE_URL)) {
            $store['hasMap'] = $seller->link_map;
        }

        $sameAs = array_values(array_filter([
            filter_var($seller->instagram, FILTER_VALIDATE_URL) ? $seller->instagram : null,
            filter_var($seller->link, FILTER_VALIDATE_URL) ? $seller->link : null,
        ]));

        if ($sameAs !== []) {
            $store['sameAs'] = $sameAs;
        }

        return $store;
    }

    /** @return array<string, mixed>|null */
    private function address(Seller $seller, ?Settlement $settlement): ?array
    {
        $street = trim((string) $seller->address);
        $locality = $settlement?->name ?: trim((string) $seller->town);

        if ($street === '' && $locality === '') {
            return null;
        }

        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $street ?: null,
            'addressLocality' => $locality ?: null,
            'addressCountry' => (string) config('seo.country', 'MD'),
        ]);
    }

    private function canonicalProductUrl(Product $product): string
    {
        $settlement = $product->seller?->settlement;

        return $this->regionalRoutes() && $settlement
            ? route('regional.product.show', ['location' => $settlement->slug, 'alias' => $product->alias])
            : route('product.show', ['alias' => $product->alias]);
    }

    private function canonicalSellerUrl(Seller $seller): string
    {
        $settlement = $seller->settlement;

        return $this->regionalRoutes() && $settlement
            ? route('regional.seller', ['location' => $settlement->slug, 'alias' => $seller->alias])
            : route('seller', ['alias' => $seller->alias]);
    }

    private function regionalRoutes(): bool
    {
        return (bool) config('regionality.urls_enabled') && Route::has('regional.product.show');
    }

    private function plain(?string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
    }
}
