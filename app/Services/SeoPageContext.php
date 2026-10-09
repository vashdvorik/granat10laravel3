<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Что показывает текущая публичная страница: товар, категория, магазин, город и счётчики для описаний.
 * Все SEO-классы (заголовки, описания, canonical, микроразметка) берут данные отсюда, поэтому страница
 * загружается один раз за запрос, а город для товара и магазина — это город продавца, а не выбранный посетителем.
 */
class SeoPageContext
{
    public const HOME = 'home';

    public const CATALOG = 'catalog';

    public const CATEGORY = 'category';

    public const PRODUCT = 'product';

    public const SELLER = 'seller';

    public const SHOPS = 'shops';

    public const SALE = 'sale';

    public const OTHER = 'other';

    private ?Product $product = null;

    private ?Category $category = null;

    private ?Seller $seller = null;

    /** @var array<string, mixed> */
    private array $memo = [];

    public function __construct(private readonly RegionalContext $regional) {}

    /** Модели, которые контроллер уже загрузил для страницы: повторно в базу за ними не ходим. */
    public function useViewData(array $data): void
    {
        $product = $data['product'] ?? null;
        $category = $data['category'] ?? null;
        $seller = $data['seller'] ?? null;

        $this->product = $product instanceof Product ? $product : $this->product;
        $this->category = $category instanceof Category ? $category : $this->category;
        $this->seller = $seller instanceof Seller ? $seller : $this->seller;
    }

    public function kind(Request $request): string
    {
        return match (true) {
            $request->routeIs('regional.home', 'home') => self::HOME,
            $request->routeIs('regional.catalog.index', 'catalog.index') => self::CATALOG,
            $request->routeIs('regional.catalog.show', 'catalog.show') => self::CATEGORY,
            $request->routeIs('regional.product.show', 'product.show') => self::PRODUCT,
            $request->routeIs('regional.seller', 'seller') => self::SELLER,
            $request->routeIs('regional.shops', 'shops') => self::SHOPS,
            $request->routeIs('regional.catalog.sale', 'catalog.sale') => self::SALE,
            default => self::OTHER,
        };
    }

    public function product(Request $request): ?Product
    {
        if ($this->kind($request) !== self::PRODUCT) {
            return null;
        }

        $this->product ??= Product::query()->where('alias', $request->route('alias'))->first();
        $this->product?->loadMissing(['seller.settlement', 'category', 'images']);

        return $this->product;
    }

    public function category(Request $request): ?Category
    {
        $kind = $this->kind($request);

        if ($kind === self::PRODUCT) {
            return $this->product($request)?->category;
        }

        if ($kind !== self::CATEGORY) {
            return null;
        }

        return $this->category ??= Category::query()->where('alias', $request->route('alias'))->first();
    }

    public function seller(Request $request): ?Seller
    {
        $kind = $this->kind($request);

        if ($kind === self::PRODUCT) {
            return $this->product($request)?->seller;
        }

        if ($kind !== self::SELLER) {
            return null;
        }

        $this->seller ??= Seller::query()->where('alias', $request->route('alias'))->first();
        $this->seller?->loadMissing('settlement');

        return $this->seller;
    }

    /**
     * Город страницы. Для товара и магазина — город продавца (страница принадлежит ему, кто бы её ни открыл),
     * для остальных страниц — город из адреса или выбранный посетителем.
     */
    public function settlement(Request $request): ?Settlement
    {
        $own = match ($this->kind($request)) {
            self::PRODUCT => $this->product($request)?->seller?->settlement,
            self::SELLER => $this->seller($request)?->settlement,
            default => null,
        };

        return $own ?? $this->regional->settlement();
    }

    /**
     * Счётчики для описания: сколько товаров и магазинов, от какой цены.
     * Берутся по текущему городу страницы и кешируются на несколько минут.
     *
     * @return array{products: int, sellers: int, min_price: ?float, max_price: ?float}
     */
    public function stats(Request $request): array
    {
        $kind = $this->kind($request);
        $category = $kind === self::CATEGORY ? $this->category($request) : null;
        $seller = $kind === self::SELLER ? $this->seller($request) : null;
        $settlement = $kind === self::SELLER ? null : $this->regional->settlement();

        $key = implode(':', [
            'seo-stats', $kind, $category?->id ?? 0, $seller?->id ?? 0, $settlement?->id ?? 0,
        ]);

        return $this->memo[$key] ??= Cache::remember($key, (int) config('seo.stats_ttl', 600), function () use ($kind, $category, $seller, $settlement): array {
            $context = new RegionalContext();
            $context->setSettlement($settlement);

            $products = Product::query()->visible()->forRegionalContext($context);

            if ($category) {
                $products->whereIn('category_id', Category::descendantsAndSelf($category->id)->pluck('id'));
            }

            if ($seller) {
                $products->where('seller_id', $seller->id);
            }

            if ($kind === self::SALE) {
                $products->whereNotNull('lowprice')->whereColumn('lowprice', '<', 'price');
            }

            $row = (clone $products)
                ->selectRaw('COUNT(*) as total, MIN(CASE WHEN lowprice IS NOT NULL AND lowprice < price THEN lowprice ELSE price END) as min_price, MAX(price) as max_price')
                ->toBase()
                ->first();

            $sellers = $seller
                ? 1
                : (clone $products)->distinct()->count('seller_id');

            return [
                'products' => (int) ($row->total ?? 0),
                'sellers' => (int) $sellers,
                'min_price' => isset($row->min_price) ? (float) $row->min_price : null,
                'max_price' => isset($row->max_price) ? (float) $row->max_price : null,
            ];
        });
    }

    /**
     * Города, где сейчас есть товары: для главной страницы без выбранного города и ссылок в подвале.
     *
     * @return Collection<int, Settlement>
     */
    public function citiesWithProducts(int $limit = 12): Collection
    {
        $key = 'seo-cities:'.$limit;

        return $this->memo[$key] ??= Cache::remember($key, (int) config('seo.stats_ttl', 600), function () use ($limit): Collection {
            return Settlement::query()
                ->active()
                ->whereHas('region', fn ($query) => $query->where('is_active', true))
                ->whereHas('sellers', fn ($sellers) => $sellers->whereHas('products', fn ($products) => $products->visible()))
                ->withCount(['sellers as visible_sellers_count' => fn ($sellers) => $sellers->whereHas('products', fn ($products) => $products->visible())])
                ->orderByDesc('visible_sellers_count')
                ->ordered()
                ->limit($limit)
                ->get();
        });
    }
}
