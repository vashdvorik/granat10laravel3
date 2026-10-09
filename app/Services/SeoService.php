<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use App\Support\PageTitle;
use App\Support\Plural;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Заголовок, описание, H1, canonical и robots публичных страниц.
 *
 * Задача — чтобы поисковик понял: это местный маркетплейс. Поэтому в каждом заголовке есть то, что люди вводят
 * («купить»), и город в правильном падеже («в Тирасполе»), а у товара и магазина — город самого продавца.
 */
class SeoService
{
    private const TITLE_NAME_LIMIT = 52;

    private const DESCRIPTION_LIMIT = 158;

    public function __construct(
        private readonly SeoPageContext $page,
    ) {}

    // ------------------------------------------------------------------ canonical и robots

    public function canonical(Request $request): string
    {
        $url = $this->canonicalBase($request);
        $page = (int) $request->query('page', 1);

        if ($page > 1 && ! $this->hasCatalogFilters($request)) {
            return $url.'?page='.$page;
        }

        return $url;
    }

    /**
     * Товар и магазин живут по двум адресам (общему и городскому). Главным считается адрес города продавца:
     * так у страницы один адрес и поисковик понимает, к какому городу она относится.
     */
    private function canonicalBase(Request $request): string
    {
        $kind = $this->page->kind($request);

        if (! config('regionality.urls_enabled') || ! Route::has('regional.product.show') || ! in_array($kind, [SeoPageContext::PRODUCT, SeoPageContext::SELLER], true)) {
            return $request->url();
        }

        $settlement = $this->page->settlement($request);
        $alias = $request->route('alias');

        if (! $settlement || ! is_string($alias) || $settlement->slug === '') {
            return $request->url();
        }

        return $kind === SeoPageContext::PRODUCT
            ? route('regional.product.show', ['location' => $settlement->slug, 'alias' => $alias])
            : route('regional.seller', ['location' => $settlement->slug, 'alias' => $alias]);
    }

    public function robots(Request $request): string
    {
        if ($this->isTechnicalPage($request) || $this->hasCatalogFilters($request) || $this->isEmptyPage($request)) {
            return 'noindex, follow';
        }

        return 'index, follow';
    }

    /** Пустые страницы (категория без товаров, магазин без товаров, город без товаров) в поиск не отдаём. */
    private function isEmptyPage(Request $request): bool
    {
        return match ($this->page->kind($request)) {
            SeoPageContext::CATEGORY, SeoPageContext::SELLER, SeoPageContext::CATALOG, SeoPageContext::SALE => $this->page->stats($request)['products'] === 0,
            default => false,
        };
    }

    // ------------------------------------------------------------------ заголовок

    public function title(Request $request): string
    {
        $kind = $this->page->kind($request);
        $settlement = $this->page->settlement($request);
        $in = $this->in($settlement);

        if ($kind === SeoPageContext::HOME && $settlement?->meta_title) {
            return $settlement->meta_title;
        }

        return match ($kind) {
            SeoPageContext::HOME => PageTitle::site($settlement
                ? 'Купить '.$in.': товары и магазины'
                : 'Купить в ПМР: товары и магазины Приднестровья'),
            SeoPageContext::CATALOG => PageTitle::site('Каталог товаров '.($settlement ? $in : 'ПМР').' — купить у местных продавцов'),
            SeoPageContext::CATEGORY => PageTitle::site($this->categoryTitle($request, $in)),
            SeoPageContext::PRODUCT => PageTitle::site($this->productTitle($request, $in)),
            SeoPageContext::SELLER => PageTitle::site($this->sellerTitle($request, $in)),
            SeoPageContext::SHOPS => PageTitle::site('Магазины '.($settlement ? $in : 'ПМР').' — каталог местных продавцов'),
            SeoPageContext::SALE => PageTitle::site('Скидки и акции '.($settlement ? $in : 'в ПМР').' — купить со скидкой'),
            default => '',
        };
    }

    private function categoryTitle(Request $request, string $in): string
    {
        $name = $this->page->category($request)?->name ?: 'Каталог';
        $min = $this->page->stats($request)['min_price'];

        return $name.' — купить '.($in ?: 'в Приднестровье').($min !== null ? ', цены от '.$this->price($min).' руб.' : ': цены, наличие');
    }

    private function productTitle(Request $request, string $in): string
    {
        $product = $this->page->product($request);
        $name = $this->fit($product?->name ?: 'Товар', self::TITLE_NAME_LIMIT);
        $price = $product ? $this->currentPrice($product) : null;

        return $name.' — купить '.($in ?: 'в ПМР').($price ? ', цена '.$this->price($price).' руб.' : '');
    }

    private function sellerTitle(Request $request, string $in): string
    {
        $name = $this->fit($this->sellerName($this->page->seller($request)) ?: 'Магазин', self::TITLE_NAME_LIMIT);

        return $name.' '.($in ?: 'в ПМР').' — товары, цены, контакты';
    }

    // ------------------------------------------------------------------ H1

    /** Главный заголовок страницы-списка; null — у страницы свой заголовок (название товара или магазина). */
    public function heading(Request $request): ?string
    {
        $kind = $this->page->kind($request);
        $settlement = $this->page->settlement($request);
        $in = $this->in($settlement);

        return match ($kind) {
            SeoPageContext::HOME => $settlement ? 'Товары и магазины '.$in : 'Маркетплейс Приднестровья',
            SeoPageContext::CATALOG => 'Каталог товаров '.($settlement ? $in : 'ПМР'),
            SeoPageContext::CATEGORY => ($this->page->category($request)?->name ?: 'Каталог').($settlement ? ' '.$in : ''),
            SeoPageContext::SHOPS => 'Магазины '.($settlement ? $in : 'ПМР'),
            SeoPageContext::SALE => 'Скидки и акции '.($settlement ? $in : 'в ПМР'),
            default => null,
        };
    }

    // ------------------------------------------------------------------ описание

    public function description(Request $request): string
    {
        $kind = $this->page->kind($request);
        $settlement = $this->page->settlement($request);
        $in = $this->in($settlement);

        if ($kind === SeoPageContext::HOME && $settlement?->meta_description) {
            return $settlement->meta_description;
        }

        $text = match ($kind) {
            SeoPageContext::HOME => $this->homeDescription($request, $settlement, $in),
            SeoPageContext::CATALOG => $this->catalogDescription($request, $in),
            SeoPageContext::CATEGORY => $this->categoryDescription($request, $in),
            SeoPageContext::PRODUCT => $this->productDescription($request, $in),
            SeoPageContext::SELLER => $this->sellerDescription($request, $in),
            SeoPageContext::SHOPS => $this->shopsDescription($request, $in),
            SeoPageContext::SALE => $this->sentences(
                'Товары со скидкой '.($in ?: 'в ПМР').': актуальные акции местных магазинов, старая и новая цена',
                'Выбирайте и заказывайте онлайн, оплата продавцу при получении',
            ),
            default => '',
        };

        return $this->fit($text, self::DESCRIPTION_LIMIT);
    }

    private function homeDescription(Request $request, ?Settlement $settlement, string $in): string
    {
        $offer = $this->offerSummary($this->page->stats($request));

        if ($settlement) {
            return $this->sentences(
                'BIMM — местный маркетплейс '.$in.': '.($offer ?: 'товары и магазины рядом с вами'),
                'Цены и наличие у местных продавцов, заказ онлайн, оплата продавцу при получении',
            );
        }

        $cities = $this->page->citiesWithProducts(3)->map(fn (Settlement $city): string => $city->nameIn());
        $where = $cities->isEmpty() ? 'в Приднестровье' : 'в '.$this->joinList($cities->all());

        return $this->sentences(
            'BIMM — маркетплейс Приднестровья: товары и магазины '.$where,
            $offer,
            'Цены и наличие у местных продавцов, заказ онлайн, оплата продавцу при получении',
        );
    }

    private function catalogDescription(Request $request, string $in): string
    {
        $offer = $this->offerSummary($this->page->stats($request));

        return $this->sentences(
            'Каталог товаров '.($in ?: 'в ПМР').': '.($offer ?: 'товары местных магазинов'),
            'Сравнивайте цены местных продавцов, выбирайте нужное и заказывайте онлайн',
        );
    }

    private function categoryDescription(Request $request, string $in): string
    {
        $name = $this->page->category($request)?->name ?: 'Товары';
        $offer = $this->offerSummary($this->page->stats($request));

        return $this->sentences(
            $name.' '.($in ?: 'в Приднестровье').': '.($offer ?: 'товары местных магазинов'),
            'Сравните цены местных продавцов, закажите онлайн — оплата продавцу при получении',
        );
    }

    private function productDescription(Request $request, string $in): string
    {
        $product = $this->page->product($request);

        if (! $product) {
            return '';
        }

        $price = $this->currentPrice($product);
        $sellerName = $this->sellerName($product->seller);
        $about = $this->plainText($product->desc);

        return $this->sentences(
            $product->name.' '.($in ?: 'в ПМР').($price ? ' — '.$this->price($price).' руб.' : ''),
            $sellerName ? 'Продавец: '.$sellerName : '',
            $about !== '' ? $about : 'Закажите онлайн, оплата продавцу при получении',
        );
    }

    private function sellerDescription(Request $request, string $in): string
    {
        $seller = $this->page->seller($request);

        if (! $seller) {
            return '';
        }

        $head = ($this->sellerName($seller) ?: 'Магазин').' '.($in ?: 'в ПМР').': ';
        $about = $this->plainText($seller->description);

        if ($about !== '') {
            return $this->sentences($head.$about);
        }

        $count = $this->page->stats($request)['products'];
        $goods = $count > 0 ? $count.' '.Plural::ru($count, 'товар', 'товара', 'товаров').' с ценами' : 'товары с ценами';

        return $this->sentences(
            $head.$goods.', адрес и телефон магазина',
            'Закажите онлайн или приезжайте за покупкой',
        );
    }

    private function shopsDescription(Request $request, string $in): string
    {
        $count = $this->page->stats($request)['sellers'];
        $shops = $count > 0 ? $count.' '.Plural::ru($count, 'магазин', 'магазина', 'магазинов') : 'Магазины';

        return $this->sentences(
            $shops.' '.($in ?: 'в ПМР').': адреса, телефоны и товары с ценами',
            'Выберите местного продавца и закажите онлайн',
        );
    }

    /** Склеивает предложения, ставя точку только там, где её ещё нет («…руб.» + «Продавец…» без «..»). */
    private function sentences(string ...$parts): string
    {
        $sentences = [];

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            $sentences[] = preg_match('/[.!?…]$/u', $part) ? $part : $part.'.';
        }

        return implode(' ', $sentences);
    }

    // ------------------------------------------------------------------ для соцсетей

    /** Картинка для превью в соцсетях и мессенджерах: фото товара или логотип магазина. */
    public function ogImage(Request $request): ?string
    {
        $kind = $this->page->kind($request);

        if ($kind === SeoPageContext::PRODUCT) {
            $images = $this->page->product($request)?->images;
            $main = $images?->sortBy(fn ($image) => sprintf('%d-%08d', $image->is_main ? 0 : 1, $image->order ?? 0))->first();

            return $main ? url(Storage::url($main->path)) : null;
        }

        if ($kind === SeoPageContext::SELLER && ($photo = $this->page->seller($request)?->photo)) {
            return url(Storage::url($photo));
        }

        return null;
    }

    public function ogType(Request $request): string
    {
        return $this->page->kind($request) === SeoPageContext::PRODUCT ? 'product' : 'website';
    }

    // ------------------------------------------------------------------ помощники

    /** «в Тирасполе» или пустая строка, если город не определён. */
    private function in(?Settlement $settlement): string
    {
        return $settlement ? $settlement->inPhrase() : '';
    }

    /** «120 товаров от 8 магазинов, цены от 350 руб.» */
    private function offerSummary(array $stats): string
    {
        if ($stats['products'] === 0) {
            return '';
        }

        $text = $stats['products'].' '.Plural::ru($stats['products'], 'товар', 'товара', 'товаров');

        if ($stats['sellers'] > 0) {
            $text .= ' от '.$stats['sellers'].' '.Plural::ru($stats['sellers'], 'магазина', 'магазинов', 'магазинов');
        }

        if ($stats['min_price'] !== null) {
            $text .= ', цены от '.$this->price($stats['min_price']).' руб.';
        }

        return $text;
    }

    /** «Тирасполе, Бендерах и Рыбнице» */
    private function joinList(array $items): string
    {
        if (count($items) <= 1) {
            return (string) ($items[0] ?? '');
        }

        $last = array_pop($items);

        return implode(', ', $items).' и '.$last;
    }

    private function currentPrice(Product $product): ?float
    {
        $price = $product->lowprice !== null && (float) $product->lowprice > 0 && (float) $product->lowprice < (float) $product->price
            ? (float) $product->lowprice
            : (float) $product->price;

        return $price > 0 ? $price : null;
    }

    private function price(float $price): string
    {
        return rtrim(rtrim(number_format($price, 2, '.', ' '), '0'), '.');
    }

    private function sellerName(?Seller $seller): string
    {
        return $seller ? trim((string) $seller->name, "\"'«» \t\n\r") : '';
    }

    private function plainText(?string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
    }

    /** Обрезает текст по границе слова и добавляет многоточие. */
    private function fit(string $text, int $limit): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit - 1);
        $space = mb_strrpos($cut, ' ');

        if ($space !== false && $space > $limit * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, " ,.;:—-").'…';
    }

    private function isTechnicalPage(Request $request): bool
    {
        return $request->routeIs(
            'search',
            'search.loadMore',
            'regional.search',
            'regional.search.loadMore',
            'orders.*',
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
