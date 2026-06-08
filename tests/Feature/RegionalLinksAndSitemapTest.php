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
            ->assertDontSee(route('regional.seller', [
                'location' => $otherSettlement->slug,
                'alias' => $seller->alias,
            ]), false);
    }
}
