<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Services\CatalogPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CatalogPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private CatalogPageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Session::flush();
        $this->service = new CatalogPageService;
    }

    public function test_catalog_page_data_applies_current_price_filter(): void
    {
        $seller = $this->createSellerWithPackage();
        $category = Category::factory()->create();
        $cheapProduct = $this->createVisibleProduct($seller, $category, [
            'name' => 'Cheap_Filter_Product',
            'price' => 100,
        ]);
        $expensiveProduct = $this->createVisibleProduct($seller, $category, [
            'name' => 'Expensive_Filter_Product',
            'price' => 9999,
        ]);

        $request = Request::create('/catalog', 'GET', [
            'min_price' => 50,
            'max_price' => 200,
        ]);

        $products = $this->service->getCatalogPageData($request)['products'];

        $this->assertTrue($products->contains($cheapProduct));
        $this->assertFalse($products->contains($expensiveProduct));
    }

    public function test_catalog_products_filter_by_seller_alias(): void
    {
        $category = Category::factory()->create();
        $targetSeller = $this->createSellerWithPackage(['alias' => 'target-seller']);
        $otherSeller = $this->createSellerWithPackage(['alias' => 'other-seller']);
        $targetProduct = $this->createVisibleProduct($targetSeller, $category, ['name' => 'Target_Product']);
        $otherProduct = $this->createVisibleProduct($otherSeller, $category, ['name' => 'Other_Product']);

        $request = Request::create('/catalog', 'GET', ['seller' => $targetSeller->alias]);

        $products = $this->service->getCatalogProducts($request);

        $this->assertTrue($products->contains($targetProduct));
        $this->assertFalse($products->contains($otherProduct));
    }

    public function test_category_page_data_returns_category_products_and_sellers(): void
    {
        $targetCategory = Category::factory()->create(['is_active' => true]);
        $otherCategory = Category::factory()->create(['is_active' => true]);
        $targetSeller = $this->createSellerWithPackage(['alias' => 'category-seller']);
        $otherSeller = $this->createSellerWithPackage(['alias' => 'outside-seller']);
        $targetProduct = $this->createVisibleProduct($targetSeller, $targetCategory, ['name' => 'Category_Product']);
        $otherProduct = $this->createVisibleProduct($otherSeller, $otherCategory, ['name' => 'Outside_Product']);

        $data = $this->service->getCategoryPageData(
            $targetCategory,
            Request::create('/catalog/'.$targetCategory->alias)
        );

        $this->assertTrue($data['products']->contains($targetProduct));
        $this->assertFalse($data['products']->contains($otherProduct));
        $this->assertTrue($data['sellers']->contains($targetSeller));
        $this->assertFalse($data['sellers']->contains($otherSeller));
    }

    public function test_sale_products_return_only_discounted_visible_products(): void
    {
        $seller = $this->createSellerWithPackage();
        $category = Category::factory()->create();
        $saleProduct = $this->createVisibleProduct($seller, $category, [
            'name' => 'Sale_Product',
            'lowprice' => 90,
            'sale_started_at' => now(),
        ]);
        $regularProduct = $this->createVisibleProduct($seller, $category, [
            'name' => 'Regular_Product',
            'lowprice' => null,
        ]);

        $products = $this->service->getSaleProducts();

        $this->assertTrue($products->contains($saleProduct));
        $this->assertFalse($products->contains($regularProduct));
    }

    public function test_search_products_filter_by_text_and_category(): void
    {
        $seller = $this->createSellerWithPackage();
        $targetCategory = Category::factory()->create(['is_active' => true]);
        $otherCategory = Category::factory()->create(['is_active' => true]);
        $targetProduct = $this->createVisibleProduct($seller, $targetCategory, [
            'name' => 'Needle Search Product',
        ]);
        $otherProduct = $this->createVisibleProduct($seller, $otherCategory, [
            'name' => 'Needle Outside Product',
        ]);

        $products = $this->service->getSearchProducts('Needle', $targetCategory->alias);

        $this->assertTrue($products->contains($targetProduct));
        $this->assertFalse($products->contains($otherProduct));
    }

    public function test_search_requires_every_meaningful_word(): void
    {
        $seller = $this->createSellerWithPackage();
        $category = Category::factory()->create();
        $matchingProduct = $this->createVisibleProduct($seller, $category, [
            'name' => 'Красная зимняя куртка',
        ]);
        $partialProduct = $this->createVisibleProduct($seller, $category, [
            'name' => 'Красная рубашка',
        ]);

        $products = $this->service->getSearchProducts('  Красная   куртка ');

        $this->assertTrue($products->contains($matchingProduct));
        $this->assertFalse($products->contains($partialProduct));
    }

    public function test_search_orders_exact_name_before_description_match(): void
    {
        $seller = $this->createSellerWithPackage();
        $category = Category::factory()->create();
        $descriptionMatch = $this->createVisibleProduct($seller, $category, [
            'name' => 'Обычный товар',
            'desc' => 'Содержит точный поисковый запрос Телефон',
            'is_vip' => false,
        ]);
        $exactMatch = $this->createVisibleProduct($seller, $category, [
            'name' => 'Телефон',
            'is_vip' => false,
        ]);

        $products = $this->service->getSearchProducts('Телефон');

        $this->assertSame($exactMatch->id, $products->first()->id);
        $this->assertTrue($products->contains($descriptionMatch));
    }

    public function test_search_products_can_be_sorted_by_effective_price(): void
    {
        $seller = $this->createSellerWithPackage();
        $category = Category::factory()->create();
        $expensiveProduct = $this->createVisibleProduct($seller, $category, [
            'name' => 'Search Sort Phone Expensive',
            'price' => 3000,
            'lowprice' => null,
        ]);
        $cheapProduct = $this->createVisibleProduct($seller, $category, [
            'name' => 'Search Sort Phone Cheap',
            'price' => 2000,
            'lowprice' => 500,
        ]);

        $products = $this->service->getSearchProducts('Search Sort Phone', '', 'price_asc');

        $this->assertSame($cheapProduct->id, $products->first()->id);
        $this->assertTrue($products->contains($expensiveProduct));
    }

    public function test_search_treats_sql_wildcards_as_regular_characters(): void
    {
        $seller = $this->createSellerWithPackage();
        $category = Category::factory()->create();
        $regularProduct = $this->createVisibleProduct($seller, $category, [
            'name' => 'Обычный товар',
        ]);

        $products = $this->service->getSearchProducts('%');

        $this->assertFalse($products->contains($regularProduct));
    }

    private function createSellerWithPackage(array $sellerAttributes = []): Seller
    {
        $seller = Seller::factory()->create($sellerAttributes);
        $package = Package::factory()->withLimits(50, 10)->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);

        return $seller;
    }

    private function createVisibleProduct(Seller $seller, Category $category, array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
        ], $attributes));
    }
}
