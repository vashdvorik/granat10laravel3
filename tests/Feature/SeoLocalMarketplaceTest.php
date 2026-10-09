<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use App\Services\DesignManager;
use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

/**
 * Локальное SEO: каждая страница однозначно говорит, в каком городе она и что здесь можно купить
 * («Мыши — купить в Тирасполе, цены от 350 руб.»), а микроразметка связывает товар с магазином и городом.
 */
class SeoLocalMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private Settlement $tiraspol;

    private Settlement $bendery;

    private Seller $techno;

    private Seller $hub;

    private Category $mice;

    private Product $mouse;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('regionality.enabled', true);
        config()->set('regionality.urls_enabled', true);
        config()->set('regionality.seo_enabled', true);
        config()->set('seo.country', 'MD');

        $this->tiraspol = Settlement::factory()->create(['name' => 'Тирасполь', 'slug' => 'tiraspol', 'sort_order' => 1]);
        $this->bendery = Settlement::factory()->create(['name' => 'Бендеры', 'slug' => 'bendery', 'sort_order' => 2]);

        $this->techno = Seller::factory()->for($this->tiraspol)->create([
            'name' => 'Техномир', 'alias' => 'tehnomir', 'address' => 'ул. Ленина, 12', 'phone' => '+373 533 11111',
            'description' => 'Компьютерная техника и аксессуары.',
        ]);
        $this->hub = Seller::factory()->for($this->bendery)->create(['name' => 'Хаб', 'alias' => 'hab', 'description' => null]);

        $this->mice = Category::factory()->create(['name' => 'Мыши', 'alias' => 'myshi']);

        $this->mouse = Product::factory()->create([
            'seller_id' => $this->techno->id, 'category_id' => $this->mice->id, 'is_active' => true,
            'name' => 'Мышь Logitech M185', 'alias' => 'mysh-logitech', 'price' => 450, 'lowprice' => 350,
            'desc' => 'Беспроводная мышь для дома и офиса.',
        ]);
        Product::factory()->create([
            'seller_id' => $this->hub->id, 'category_id' => $this->mice->id, 'is_active' => true,
            'name' => 'Мышь игровая', 'alias' => 'mysh-igrovaya', 'price' => 900, 'lowprice' => null,
        ]);
    }

    /** @return array<string, array{0: string}> */
    public static function themes(): array
    {
        return ['legacy' => ['legacy'], 'new' => ['new'], 'joom' => ['joom']];
    }

    private function headSection(string $html): string
    {
        return (string) substr($html, 0, (int) strpos($html, '</head>'));
    }

    /** @return array<int, array<string, mixed>> */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        return array_map(function (string $json): array {
            $decoded = json_decode($json, true);
            $this->assertIsArray($decoded, 'JSON-LD должен быть корректным JSON: '.$json);

            return $decoded;
        }, $matches[1]);
    }

    /** @param  array<int, array<string, mixed>>  $blocks */
    private function ofType(array $blocks, string $type): ?array
    {
        foreach ($blocks as $block) {
            if (($block['@type'] ?? null) === $type) {
                return $block;
            }
        }

        return null;
    }

    private function meta(string $html, string $name): ?string
    {
        return preg_match('#<meta name="'.preg_quote($name, '#').'" content="([^"]*)"#u', $html, $m) ? html_entity_decode($m[1]) : null;
    }

    private function title(string $html): string
    {
        preg_match('#<title>(.*?)</title>#su', $html, $m);

        return trim(html_entity_decode($m[1] ?? ''));
    }

    // ------------------------------------------------------------------ товар

    /** @dataProvider themes */
    public function test_product_page_sells_the_purchase_in_the_sellers_city(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);

        $html = $this->get(route('regional.product.show', ['location' => 'tiraspol', 'alias' => 'mysh-logitech']))
            ->assertOk()
            ->getContent();

        $this->assertSame('Мышь Logitech M185 — купить в Тирасполе, цена 350 руб. — BIMM', $this->title($html));
        $this->assertStringStartsWith('Мышь Logitech M185 в Тирасполе — 350 руб. Продавец: Техномир. Беспроводная мышь', (string) $this->meta($html, 'description'));
        $this->assertStringContainsString(
            '<link rel="canonical" href="'.route('regional.product.show', ['location' => 'tiraspol', 'alias' => 'mysh-logitech']).'">',
            $html
        );
        $this->assertStringContainsString('<meta name="robots" content="index, follow">', $html);
        $this->assertStringContainsString('<meta property="og:type" content="product">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Мышь Logitech M185 — купить в Тирасполе', $html);
    }

    public function test_global_product_address_points_to_the_city_address_of_the_seller(): void
    {
        $html = $this->get(route('product.show', ['alias' => 'mysh-logitech']))->assertOk()->getContent();

        $this->assertStringContainsString(
            '<link rel="canonical" href="'.route('regional.product.show', ['location' => 'tiraspol', 'alias' => 'mysh-logitech']).'">',
            $html
        );
        $this->assertStringContainsString('в Тирасполе', $this->title($html));
    }

    public function test_product_uses_the_sellers_city_even_if_the_visitor_chose_another_one(): void
    {
        // посетитель из Бендер открыл товар тираспольского магазина по прямой ссылке
        $html = $this->withSession(['regional_settlement_id' => $this->bendery->id])
            ->get(route('regional.product.show', ['location' => 'tiraspol', 'alias' => 'mysh-logitech']))
            ->getContent();

        $this->assertStringContainsString('в Тирасполе', $this->title((string) $html));
        $this->assertStringNotContainsString('в Бендерах', $this->title((string) $html));
    }

    /** @dataProvider themes */
    public function test_product_structured_data_ties_the_offer_to_the_local_store(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);

        $blocks = $this->jsonLd($this->get(route('regional.product.show', ['location' => 'tiraspol', 'alias' => 'mysh-logitech']))->getContent());
        $product = $this->ofType($blocks, 'Product');
        $breadcrumbs = $this->ofType($blocks, 'BreadcrumbList');

        $this->assertNotNull($product, 'У товара должна быть разметка Product');
        $this->assertSame('Мышь Logitech M185', $product['name']);
        $this->assertSame('350.00', $product['offers']['price']);
        $this->assertSame('RUB', $product['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $product['offers']['availability']);
        $this->assertSame(route('regional.product.show', ['location' => 'tiraspol', 'alias' => 'mysh-logitech']), $product['url']);
        $this->assertSame('Мыши', $product['category']);

        $seller = $product['offers']['seller'];
        $this->assertSame('Store', $seller['@type']);
        $this->assertSame('Техномир', $seller['name']);
        $this->assertSame('Тирасполь', $seller['address']['addressLocality']);
        $this->assertSame('ул. Ленина, 12', $seller['address']['streetAddress']);
        $this->assertSame('MD', $seller['address']['addressCountry']);
        $this->assertSame('Тирасполь', $seller['areaServed']['name']);
        $this->assertSame('+37353311111', $seller['telephone']);
        $this->assertSame('Тирасполь', $product['offers']['availableAtOrFrom']['address']['addressLocality']);

        $this->assertNotNull($breadcrumbs);
        $names = array_column($breadcrumbs['itemListElement'], 'name');
        $this->assertSame(['Главная', 'Каталог', 'Мыши', 'Мышь Logitech M185'], $names);
        $this->assertSame(range(1, 4), array_column($breadcrumbs['itemListElement'], 'position'));
    }

    public function test_structured_data_is_emitted_once_per_type(): void
    {
        app(DesignManager::class)->setActive('legacy');

        $blocks = $this->jsonLd($this->get(route('regional.product.show', ['location' => 'tiraspol', 'alias' => 'mysh-logitech']))->getContent());
        $types = array_column($blocks, '@type');

        $this->assertSame(1, count(array_keys($types, 'Product')));
        $this->assertSame(1, count(array_keys($types, 'BreadcrumbList')));
    }

    public function test_product_name_cannot_break_out_of_the_json_ld_script(): void
    {
        Product::factory()->create([
            'seller_id' => $this->techno->id, 'category_id' => $this->mice->id, 'is_active' => true,
            'name' => 'Мышь </script><script>alert(1)</script>', 'alias' => 'mysh-xss', 'price' => 100, 'lowprice' => null,
        ]);

        $html = $this->get(route('regional.product.show', ['location' => 'tiraspol', 'alias' => 'mysh-xss']))->getContent();

        $this->assertStringNotContainsString('</script><script>alert(1)</script>', $html);
        $product = $this->ofType($this->jsonLd($html), 'Product');
        $this->assertSame('Мышь </script><script>alert(1)</script>', $product['name']);
    }

    // ------------------------------------------------------------------ категория

    /** @dataProvider themes */
    public function test_category_in_a_city_targets_the_buy_query_with_the_city(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);

        $html = $this->get(route('regional.catalog.show', ['location' => 'tiraspol', 'alias' => 'myshi']))
            ->assertOk()
            ->getContent();

        $this->assertSame('Мыши — купить в Тирасполе, цены от 350 руб. — BIMM', $this->title($html));
        $this->assertSame(
            'Мыши в Тирасполе: 1 товар от 1 магазина, цены от 350 руб. Сравните цены местных продавцов, закажите онлайн — оплата продавцу при получении.',
            $this->meta($html, 'description')
        );
        $this->assertStringContainsString('Мыши в Тирасполе</h1>', $html);
        $this->assertSame(1, substr_count($html, '<h1'), 'на странице один H1');
        $this->assertStringContainsString('<meta name="robots" content="index, follow">', $html);
    }

    public function test_category_without_a_city_speaks_about_the_whole_region(): void
    {
        $html = $this->get(route('catalog.show', ['alias' => 'myshi']))->assertOk()->getContent();

        $this->assertSame('Мыши — купить в Приднестровье, цены от 350 руб. — BIMM', $this->title($html));
        $this->assertStringStartsWith('Мыши в Приднестровье: 2 товара от 2 магазинов, цены от 350 руб.', (string) $this->meta($html, 'description'));
    }

    public function test_category_breadcrumbs_include_parents(): void
    {
        $parent = Category::factory()->create(['name' => 'Компьютеры', 'alias' => 'kompyutery']);
        $this->mice->update(['parent_id' => $parent->id]);

        $blocks = $this->jsonLd($this->get(route('regional.catalog.show', ['location' => 'tiraspol', 'alias' => 'myshi']))->getContent());

        $this->assertSame(
            ['Главная', 'Каталог', 'Компьютеры', 'Мыши'],
            array_column($this->ofType($blocks, 'BreadcrumbList')['itemListElement'], 'name')
        );
    }

    // ------------------------------------------------------------------ пустые страницы

    public function test_empty_pages_are_kept_out_of_search(): void
    {
        $empty = Category::factory()->create(['name' => 'Пустая', 'alias' => 'pustaya']);
        $noGoods = Seller::factory()->for($this->tiraspol)->create(['alias' => 'bez-tovarov']);
        $emptyCity = Settlement::factory()->create(['slug' => 'empty-town']);

        foreach ([
            route('regional.catalog.show', ['location' => 'tiraspol', 'alias' => 'pustaya']),
            route('catalog.show', ['alias' => 'pustaya']),
            route('regional.seller', ['location' => 'tiraspol', 'alias' => 'bez-tovarov']),
            route('regional.catalog.index', ['location' => 'empty-town']),
            route('regional.catalog.sale', ['location' => 'bendery']),
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('<meta name="robots" content="noindex, follow">', false);
        }

        // страницы с товарами остаются в поиске
        $this->get(route('regional.catalog.show', ['location' => 'tiraspol', 'alias' => 'myshi']))
            ->assertSee('<meta name="robots" content="index, follow">', false);
        $this->assertSame('Мыши', $this->mice->name);
        $this->assertNotNull($empty);
        $this->assertNotNull($noGoods);
        $this->assertNotNull($emptyCity);
    }

    // ------------------------------------------------------------------ магазин, список магазинов, скидки

    /** @dataProvider themes */
    public function test_seller_page_is_a_local_business(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);

        $html = $this->get(route('regional.seller', ['location' => 'tiraspol', 'alias' => 'tehnomir']))->assertOk()->getContent();

        $this->assertSame('Техномир в Тирасполе — товары, цены, контакты — BIMM', $this->title($html));
        $this->assertSame('Техномир в Тирасполе: Компьютерная техника и аксессуары.', $this->meta($html, 'description'));

        $store = $this->ofType($this->jsonLd($html), 'Store');
        $this->assertNotNull($store);
        $this->assertSame('Тирасполь', $store['address']['addressLocality']);
        $this->assertSame(route('regional.seller', ['location' => 'tiraspol', 'alias' => 'tehnomir']), $store['url']);
    }

    public function test_seller_without_description_gets_a_text_with_the_number_of_goods(): void
    {
        $html = $this->get(route('regional.seller', ['location' => 'bendery', 'alias' => 'hab']))->assertOk()->getContent();

        $this->assertSame('Хаб в Бендерах — товары, цены, контакты — BIMM', $this->title($html));
        $this->assertSame(
            'Хаб в Бендерах: 1 товар с ценами, адрес и телефон магазина. Закажите онлайн или приезжайте за покупкой.',
            $this->meta($html, 'description')
        );
    }

    public function test_shops_and_sale_pages_name_the_city(): void
    {
        $shops = $this->get(route('regional.shops', ['location' => 'tiraspol']))->assertOk()->getContent();
        $this->assertSame('Магазины в Тирасполе — каталог местных продавцов — BIMM', $this->title($shops));
        $this->assertStringContainsString('Магазины в Тирасполе</h1>', $shops);

        $sale = $this->get(route('regional.catalog.sale', ['location' => 'tiraspol']))->assertOk()->getContent();
        $this->assertSame('Скидки и акции в Тирасполе — купить со скидкой — BIMM', $this->title($sale));
        $this->assertStringContainsString('Скидки и акции в Тирасполе</h1>', $sale);
    }

    // ------------------------------------------------------------------ главная

    /** @dataProvider themes */
    public function test_city_home_page_and_site_level_structured_data(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);

        $html = $this->get(route('regional.home', ['location' => 'tiraspol']))->assertOk()->getContent();

        $this->assertSame('Купить в Тирасполе: товары и магазины — BIMM', $this->title($html));
        $this->assertStringStartsWith('BIMM — местный маркетплейс в Тирасполе: 1 товар от 1 магазина, цены от 350 руб.', (string) $this->meta($html, 'description'));
        $this->assertStringContainsString('Товары и магазины в Тирасполе', $html);

        $blocks = $this->jsonLd($html);
        $organization = $this->ofType($blocks, 'Organization');
        $website = $this->ofType($blocks, 'WebSite');

        $this->assertSame('BIMM', $organization['name']);
        $this->assertSame('Тирасполь', $organization['areaServed']['name']);
        $this->assertSame('BIMM', $website['name']);
        $this->assertSame('SearchAction', $website['potentialAction']['@type']);
        $this->assertStringContainsString('?q={search_term_string}', $website['potentialAction']['target']['urlTemplate']);
    }

    public function test_home_page_without_a_city_lists_the_cities_with_goods(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame('Купить в ПМР: товары и магазины Приднестровья — BIMM', $this->title($html));
        $description = (string) $this->meta($html, 'description');
        $this->assertStringStartsWith('BIMM — маркетплейс Приднестровья: товары и магазины в Тирасполе и Бендерах. 2 товара от 2 магазинов, цены от 350 руб.', $description);
    }

    public function test_settlement_manual_seo_fields_win_over_generated_text(): void
    {
        $this->tiraspol->update(['meta_title' => 'Свой заголовок Тирасполя', 'meta_description' => 'Своё описание Тирасполя.']);

        $html = $this->get(route('regional.home', ['location' => 'tiraspol']))->getContent();

        $this->assertSame('Свой заголовок Тирасполя', $this->title($html));
        $this->assertSame('Своё описание Тирасполя.', $this->meta($html, 'description'));
    }

    public function test_city_name_in_the_title_can_be_set_by_hand(): void
    {
        $this->tiraspol->update(['name_in' => 'Тирасполе-на-Днестре']);

        $html = $this->get(route('regional.catalog.index', ['location' => 'tiraspol']))->getContent();

        $this->assertStringContainsString('в Тирасполе-на-Днестре', $this->title($html));
    }

    // ------------------------------------------------------------------ подвал и ссылки

    /** @dataProvider themes */
    public function test_footer_links_to_every_city_that_has_goods(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);
        Settlement::factory()->create(['name' => 'Пустоград', 'slug' => 'pustograd']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('regional.home', ['location' => 'tiraspol']).'" title="Купить в Тирасполе"', $html);
        $this->assertStringContainsString('href="'.route('regional.home', ['location' => 'bendery']).'" title="Купить в Бендерах"', $html);
        $this->assertStringNotContainsString('pustograd', $html, 'города без товаров в ссылки не попадают');
    }

    // ------------------------------------------------------------------ Open Graph во всех темах

    /** @dataProvider themes */
    public function test_open_graph_and_twitter_tags_exist_in_every_theme(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);

        $head = $this->headSection($this->get(route('regional.catalog.show', ['location' => 'tiraspol', 'alias' => 'myshi']))->getContent());

        foreach (['og:title', 'og:type', 'og:url', 'og:locale', 'og:site_name', 'og:description'] as $property) {
            $this->assertStringContainsString('<meta property="'.$property.'"', $head, "{$theme}: нет {$property}");
        }

        $this->assertStringContainsString('<meta property="og:site_name" content="BIMM">', $head);
        $this->assertStringContainsString('<meta name="twitter:title"', $head);
        $this->assertStringContainsString('<meta name="twitter:description"', $head);
    }

    // ------------------------------------------------------------------ устойчивость

    public function test_a_failure_in_the_seo_layer_never_breaks_the_page(): void
    {
        $this->app->bind(SeoService::class, fn () => throw new RuntimeException('SEO сломан'));

        $html = $this->get(route('regional.catalog.show', ['location' => 'tiraspol', 'alias' => 'myshi']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<meta name="robots" content="index, follow">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.route('regional.catalog.show', ['location' => 'tiraspol', 'alias' => 'myshi']).'">', $html);
        $this->assertNotSame('', $this->title($html));
    }

    public function test_seo_tags_are_output_even_when_the_regional_seo_flag_is_off(): void
    {
        config()->set('regionality.seo_enabled', false);

        $html = $this->get(route('regional.catalog.show', ['location' => 'tiraspol', 'alias' => 'myshi']))->getContent();

        $this->assertStringContainsString('<link rel="canonical"', $html);
        $this->assertNotNull($this->meta($html, 'description'));
    }

    // ------------------------------------------------------------------ технические адреса

    public function test_old_home_address_redirects_permanently(): void
    {
        $this->get('/home')->assertStatus(301)->assertRedirect('/');
    }

    public function test_robots_txt_hides_service_urls_but_not_landing_pages(): void
    {
        $body = (string) $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /*load-more', $body);
        $this->assertStringContainsString('Disallow: /location', $body);
        $this->assertStringContainsString('Disallow: /account/', $body);
        $this->assertStringNotContainsString('Disallow: /seller-help', $body);
        $this->assertStringNotContainsString('Disallow: /catalog', $body);
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $body);
    }
}
