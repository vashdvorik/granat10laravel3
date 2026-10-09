<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** sitemap.xml: только страницы, которые можно показывать в поиске, по одному адресу и с датой изменения. */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('regionality.enabled', true);
        config()->set('regionality.urls_enabled', true);
    }

    private function product(Seller $seller, Category $category, string $alias, array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'seller_id' => $seller->id, 'category_id' => $category->id, 'alias' => $alias, 'is_active' => true,
        ], $overrides));
    }

    private function sitemap(string $url = '/sitemap.xml'): string
    {
        $body = (string) $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

        $document = new DOMDocument();
        $this->assertTrue($document->loadXML($body), 'sitemap должен быть корректным XML');

        return $body;
    }

    public function test_every_listed_page_has_a_last_modified_date(): void
    {
        $city = Settlement::factory()->create(['slug' => 'tiraspol']);
        $seller = Seller::factory()->for($city)->create(['alias' => 'shop']);
        $category = Category::factory()->create(['alias' => 'cat']);
        $this->product($seller, $category, 'item');

        $body = $this->sitemap();

        $this->assertStringContainsString('<loc>'.route('regional.product.show', ['location' => 'tiraspol', 'alias' => 'item']).'</loc>', $body);
        $this->assertMatchesRegularExpression(
            '#<loc>[^<]*/tiraspol/product/item</loc>\s*<lastmod>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}</lastmod>#',
            $body
        );
        $this->assertMatchesRegularExpression('#/tiraspol/shops/shop</loc>\s*<lastmod>#', $body);
        $this->assertMatchesRegularExpression('#/catalog/cat</loc>\s*<lastmod>#', $body);
    }

    public function test_product_is_listed_once_at_the_address_of_the_sellers_city(): void
    {
        $city = Settlement::factory()->create(['slug' => 'tiraspol']);
        $seller = Seller::factory()->for($city)->create(['alias' => 'shop']);
        $this->product($seller, Category::factory()->create(), 'item');

        $body = $this->sitemap();

        $this->assertStringContainsString('/tiraspol/product/item', $body);
        $this->assertStringNotContainsString(route('product.show', ['alias' => 'item']), $body);
        $this->assertStringNotContainsString(route('seller', ['alias' => 'shop']), $body);
    }

    public function test_products_of_sellers_without_a_city_are_still_listed_at_the_general_address(): void
    {
        $seller = Seller::factory()->create(['alias' => 'nomad', 'settlement_id' => null]);
        $this->product($seller, Category::factory()->create(), 'nomad-item');

        $body = $this->sitemap();

        $this->assertStringContainsString('<loc>'.route('product.show', ['alias' => 'nomad-item']).'</loc>', $body);
        $this->assertStringContainsString('<loc>'.route('seller', ['alias' => 'nomad']).'</loc>', $body);
    }

    public function test_products_of_an_inactive_city_stay_in_the_map_at_the_general_address(): void
    {
        $city = Settlement::factory()->create(['slug' => 'closed-city', 'is_active' => false]);
        $seller = Seller::factory()->for($city)->create(['alias' => 'closed-shop']);
        $this->product($seller, Category::factory()->create(), 'closed-item');

        $body = $this->sitemap();

        $this->assertStringContainsString('<loc>'.route('product.show', ['alias' => 'closed-item']).'</loc>', $body);
        $this->assertStringNotContainsString('/closed-city/', $body);
    }

    public function test_empty_cities_are_not_listed(): void
    {
        Settlement::factory()->create(['slug' => 'empty-city']);

        $body = $this->sitemap();

        $this->assertStringNotContainsString('empty-city', $body);
    }

    public function test_hidden_products_are_not_listed(): void
    {
        $city = Settlement::factory()->create(['slug' => 'tiraspol']);
        $seller = Seller::factory()->for($city)->create();
        $category = Category::factory()->create();
        $this->product($seller, $category, 'visible-item');
        $this->product($seller, $category, 'hidden-item', ['is_active' => false]);

        $body = $this->sitemap();

        $this->assertStringContainsString('visible-item', $body);
        $this->assertStringNotContainsString('hidden-item', $body);
    }

    public function test_without_city_addresses_all_goods_use_general_addresses(): void
    {
        config()->set('regionality.urls_enabled', false);
        $city = Settlement::factory()->create(['slug' => 'tiraspol']);
        $seller = Seller::factory()->for($city)->create(['alias' => 'shop']);
        $this->product($seller, Category::factory()->create(), 'item');

        $body = $this->sitemap();

        $this->assertStringContainsString('<loc>'.route('product.show', ['alias' => 'item']).'</loc>', $body);
        $this->assertStringContainsString('<loc>'.route('seller', ['alias' => 'shop']).'</loc>', $body);
        $this->assertStringNotContainsString('/tiraspol/', $body);
    }

    public function test_large_maps_are_split_into_an_index_and_numbered_files(): void
    {
        config()->set('seo.sitemap_chunk', 3);
        $seller = Seller::factory()->create(['settlement_id' => null]);
        $category = Category::factory()->create();

        foreach (range(1, 6) as $number) {
            $this->product($seller, $category, 'item-'.$number);
        }

        $index = $this->sitemap();

        $this->assertStringContainsString('<sitemapindex', $index);
        $this->assertStringContainsString('<loc>'.route('sitemap.page', ['page' => 1]).'</loc>', $index);
        $this->assertStringContainsString('<loc>'.route('sitemap.page', ['page' => 2]).'</loc>', $index);

        $all = '';
        foreach ([1, 2, 3, 4, 5] as $page) {
            if (str_contains($index, route('sitemap.page', ['page' => $page]))) {
                $part = $this->sitemap(route('sitemap.page', ['page' => $page], false));
                $this->assertStringContainsString('<urlset', $part);
                $all .= $part;
            }
        }

        foreach (range(1, 6) as $number) {
            $this->assertStringContainsString('/product/item-'.$number.'</loc>', $all);
        }
    }

    public function test_page_beyond_the_last_file_is_not_found(): void
    {
        $this->get('/sitemap-99.xml')->assertNotFound();
    }

    public function test_small_site_gets_a_single_urlset_without_an_index(): void
    {
        $body = $this->sitemap();

        $this->assertStringContainsString('<urlset', $body);
        $this->assertStringNotContainsString('<sitemapindex', $body);
    }
}
