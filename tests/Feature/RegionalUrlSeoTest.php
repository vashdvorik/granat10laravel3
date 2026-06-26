<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\Settlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionalUrlSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('regionality.enabled', true);
        config()->set('regionality.urls_enabled', true);
        config()->set('regionality.seo_enabled', true);
    }

    public function test_regional_catalog_uses_location_from_url_and_self_canonical(): void
    {
        $settlement = Settlement::factory()->create(['slug' => 'regional-city']);
        $otherSettlement = Settlement::factory()->create(['slug' => 'other-city']);
        $category = Category::factory()->create();
        $regionalProduct = $this->createVisibleProduct($settlement, $category, 'Regional URL Product');
        $otherProduct = $this->createVisibleProduct($otherSettlement, $category, 'Other URL Product');
        $url = route('regional.catalog.index', ['location' => $settlement->slug]);

        $this->get($url)
            ->assertOk()
            ->assertSee($regionalProduct->name)
            ->assertDontSee($otherProduct->name)
            ->assertSee('<link rel="canonical" href="'.$url.'">', false)
            ->assertSee('<meta name="robots" content="index, follow">', false)
            ->assertSessionHas('regional_settlement_id', $settlement->id);
    }

    public function test_invalid_location_returns_not_found(): void
    {
        $this->get('/unknown-location/catalog')->assertNotFound();
    }

    public function test_search_ajax_and_filtered_catalog_are_noindex(): void
    {
        $settlement = Settlement::factory()->create(['slug' => 'seo-city']);

        $this->get(route('regional.search', ['location' => $settlement->slug, 'q' => 'phone']))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);

        $canonical = route('regional.catalog.index', ['location' => $settlement->slug]);

        $this->get($canonical.'?min_price=10&max_price=100')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertSee('<link rel="canonical" href="'.$canonical.'">', false);
    }

    public function test_paginated_catalog_pages_have_self_canonical_and_are_indexable(): void
    {
        $url = route('catalog.index');

        $this->get($url.'?page=2')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$url.'?page=2">', false)
            ->assertSee('<meta name="robots" content="index, follow">', false);
    }

    public function test_old_catalog_url_remains_available_with_own_canonical(): void
    {
        $url = route('catalog.index');

        $this->get($url)
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$url.'">', false);
    }

    public function test_regional_routes_pass_business_alias_instead_of_location_to_controllers(): void
    {
        $settlement = Settlement::factory()->create(['slug' => 'tiraspol']);
        $category = Category::factory()->create(['alias' => 'clothes', 'is_active' => true]);
        $product = $this->createVisibleProduct($settlement, $category, 'Regional Route Product');
        $seller = $product->seller;

        $this->get(route('regional.catalog.show', [
            'location' => $settlement->slug,
            'alias' => $category->alias,
        ]))->assertOk();

        $this->get(route('regional.product.show', [
            'location' => $settlement->slug,
            'alias' => $product->alias,
        ]))
            ->assertOk()
            ->assertSee($product->name);

        $this->get(route('regional.seller', [
            'location' => $settlement->slug,
            'alias' => $seller->alias,
        ]))
            ->assertOk()
            ->assertSee($seller->name);

        $this->get(route('regional.seller.loadMore', [
            'location' => $settlement->slug,
            'alias' => $seller->alias,
        ]))->assertOk();
    }

    private function createVisibleProduct(
        Settlement $settlement,
        Category $category,
        string $name
    ): Product {
        $seller = Seller::factory()->for($settlement)->create();
        $package = Package::factory()->withLimits(50, 10)->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);

        return Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }
}
