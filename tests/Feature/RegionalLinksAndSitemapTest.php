<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\Settlement;
use App\Services\RegionalContext;
use App\Services\RegionalUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionalLinksAndSitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('regionality.enabled', true);
        config()->set('regionality.urls_enabled', true);
        config()->set('regionality.seo_enabled', true);
    }

    public function test_regional_url_service_preserves_selected_location(): void
    {
        $settlement = Settlement::factory()->create(['slug' => 'link-city']);
        app(RegionalContext::class)->setSettlement($settlement);
        $service = app(RegionalUrlService::class);

        $this->assertSame(
            route('regional.catalog.index', ['location' => $settlement->slug]),
            $service->route('catalog.index')
        );
        $this->assertSame(
            route('regional.product.show', ['location' => $settlement->slug, 'alias' => 'phone']),
            $service->route('product.show', ['alias' => 'phone'])
        );
    }

    public function test_regional_catalog_contains_regional_links_and_metadata(): void
    {
        $settlement = Settlement::factory()->create([
            'slug' => 'meta-city',
            'name' => 'Мета-город',
        ]);
        $url = route('regional.catalog.index', ['location' => $settlement->slug]);

        $this->get($url)
            ->assertOk()
            ->assertSee(route('regional.shops', ['location' => $settlement->slug]))
            ->assertSee('Каталог товаров в Мета-город | ГРАНАТ')
            ->assertSee('Товары местных магазинов в Мета-город')
            ->assertSee('Каталог товаров в Мета-город');
    }

    public function test_sitemap_contains_only_available_regional_entities(): void
    {
        $settlement = Settlement::factory()->create(['slug' => 'sitemap-city']);
        $otherSettlement = Settlement::factory()->create(['slug' => 'empty-city']);
        $seller = Seller::factory()->for($settlement)->create(['alias' => 'sitemap-shop']);
        $package = Package::factory()->withLimits(50, 10)->create();
        $category = Category::factory()->create();
        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'alias' => 'sitemap-product',
            'is_active' => true,
        ]);

        $response = $this->get(route('sitemap'));

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('regional.catalog.index', ['location' => $settlement->slug]), false)
            ->assertSee(route('regional.seller', [
                'location' => $settlement->slug,
                'alias' => $seller->alias,
            ]), false)
            ->assertSee(route('regional.product.show', [
                'location' => $settlement->slug,
                'alias' => $product->alias,
            ]), false)
            ->assertSee(route('catalog.show', ['alias' => $category->alias]), false)
            ->assertSee(route('regional.catalog.show', [
                'location' => $settlement->slug,
                'alias' => $category->alias,
            ]), false)
            ->assertDontSee(route('regional.catalog.show', [
                'location' => $otherSettlement->slug,
                'alias' => $category->alias,
            ]), false)
            ->assertDontSee(route('regional.seller', [
                'location' => $otherSettlement->slug,
                'alias' => $seller->alias,
            ]), false);
    }

    public function test_regional_entity_pages_use_real_names_in_seo(): void
    {
        $settlement = Settlement::factory()->create([
            'slug' => 'seo-city',
            'name' => 'SEO-город',
        ]);
        $seller = $this->createSellerWithPackage($settlement, 'seo-shop');
        $category = Category::factory()->create([
            'name' => 'Ноутбуки',
            'alias' => 'laptops',
        ]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Ноутбук Pro',
            'alias' => 'laptop-pro',
            'is_active' => true,
        ]);

        $this->get(route('regional.catalog.show', [
            'location' => $settlement->slug,
            'alias' => $category->alias,
        ]))
            ->assertOk()
            ->assertSee('Ноутбуки — товары в SEO-город | ГРАНАТ');

        $this->get(route('regional.product.show', [
            'location' => $settlement->slug,
            'alias' => $product->alias,
        ]))
            ->assertOk()
            ->assertSee('Ноутбук Pro в SEO-город | ГРАНАТ')
            ->assertSee('<h1 class="product-title">', false);

        $this->get(route('regional.seller', [
            'location' => $settlement->slug,
            'alias' => $seller->alias,
        ]))
            ->assertOk()
            ->assertSee($seller->name.' в SEO-город | ГРАНАТ')
            ->assertSee('<h1 class="blog-heading text-center">', false);
    }

    public function test_regional_home_uses_settlement_manual_seo_fields(): void
    {
        $settlement = Settlement::factory()->create([
            'slug' => 'manual-seo-city',
            'meta_title' => 'Ручной SEO Title',
            'meta_description' => 'Ручное SEO-описание города.',
        ]);

        $this->get(route('regional.home', ['location' => $settlement->slug]))
            ->assertOk()
            ->assertSee('Ручной SEO Title')
            ->assertSee('Ручное SEO-описание города.');
    }

    private function createSellerWithPackage(Settlement $settlement, string $alias): Seller
    {
        $seller = Seller::factory()->for($settlement)->create(['alias' => $alias]);
        $package = Package::factory()->withLimits(50, 10)->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);

        return $seller;
    }
}
