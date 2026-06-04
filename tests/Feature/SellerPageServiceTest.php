<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Services\SellerPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SellerPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private SellerPageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->service = new SellerPageService();
    }

    public function test_get_shops_orders_by_active_products_count(): void
    {
        $firstSeller = Seller::factory()->create();
        $secondSeller = Seller::factory()->create();

        Product::factory()->count(1)->create([
            'seller_id' => $firstSeller->id,
            'is_active' => true,
        ]);
        Product::factory()->count(3)->create([
            'seller_id' => $secondSeller->id,
            'is_active' => true,
        ]);
        Product::factory()->count(5)->inactive()->create([
            'seller_id' => $firstSeller->id,
        ]);

        $sellers = $this->service->getShops();

        $this->assertSame($secondSeller->id, $sellers->first()->id);
        $this->assertSame(3, $sellers->first()->products_count);
    }

    public function test_get_seller_products_returns_only_visible_products_for_seller(): void
    {
        $package = Package::factory()->withLimits(10, 2)->create();
        $seller = Seller::factory()->create();
        $otherSeller = Seller::factory()->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
        ]);
        SellerPackage::factory()->create([
            'seller_id' => $otherSeller->id,
            'package_id' => $package->id,
            'is_active' => true,
        ]);

        $visibleProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
        Product::factory()->inactive()->create([
            'seller_id' => $seller->id,
        ]);
        Product::factory()->create([
            'seller_id' => $otherSeller->id,
            'is_active' => true,
        ]);

        $products = $this->service->getSellerProducts($seller);

        $this->assertCount(1, $products);
        $this->assertSame($visibleProduct->id, $products->first()->id);
    }

    public function test_get_seller_categories_filters_empty_root_categories_and_sets_counts(): void
    {
        $seller = Seller::factory()->create();
        $rootWithProduct = Category::factory()->create(['parent_id' => null]);
        $rootWithoutProduct = Category::factory()->create(['parent_id' => null]);
        $childWithProduct = Category::factory()->create(['parent_id' => $rootWithProduct->id]);

        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $childWithProduct->id,
        ]);

        Cache::flush();

        $categories = $this->service->getSellerCategories($seller);

        $this->assertTrue($categories->contains('id', $rootWithProduct->id));
        $this->assertFalse($categories->contains('id', $rootWithoutProduct->id));
        $this->assertSame(1, $categories->firstWhere('id', $rootWithProduct->id)->total_products);
    }
}
