<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature-тест: HTTP-эндпоинт страницы товара.
 *
 * @covers \App\Http\Controllers\ProductController::show
 */
class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // =========================================================================
    // Хелперы
    // =========================================================================

    private function createVisibleProduct(array $attrs = []): Product
    {
        $package  = Package::factory()->withLimits(50, 10)->create();
        $seller   = Seller::factory()->create();
        $category = Category::factory()->create();

        SellerPackage::factory()->create([
            'seller_id'  => $seller->id,
            'package_id' => $package->id,
            'is_active'  => true,
            'end_date'   => null,
        ]);

        return Product::factory()->create(array_merge([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ], $attrs));
    }

    // =========================================================================
    // Тесты
    // =========================================================================

    public function test_product_detail_page_returns_200_for_visible_product(): void
    {
        $product = $this->createVisibleProduct();

        $response = $this->get(route('product.show', $product->alias));

        $response->assertStatus(200);
    }

    public function test_product_detail_page_contains_product_name(): void
    {
        $product = $this->createVisibleProduct(['name' => 'Тестовый товар для теста']);

        $response = $this->get(route('product.show', $product->alias));

        $response->assertSee('Тестовый товар для теста');
    }

    public function test_product_detail_returns_404_for_inactive_product(): void
    {
        $product = $this->createVisibleProduct(['is_active' => false]);

        $response = $this->get(route('product.show', $product->alias));

        // Неактивный товар не должен открываться публично
        $response->assertStatus(404);
    }

    public function test_product_detail_returns_404_for_nonexistent_alias(): void
    {
        $response = $this->get(route('product.show', 'non-existent-product-alias-xyz'));

        $response->assertStatus(404);
    }

    public function test_product_detail_returns_404_when_seller_package_expired(): void
    {
        $package  = Package::factory()->withLimits(50, 10)->create();
        $seller   = Seller::factory()->create();
        $category = Category::factory()->create();

        SellerPackage::factory()->create([
            'seller_id'  => $seller->id,
            'package_id' => $package->id,
            'is_active'  => true,
            'end_date'   => now()->subDay()->toDateString(), // просрочен
        ]);

        $product = Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ]);

        $response = $this->get(route('product.show', $product->alias));

        $response->assertStatus(404);
    }

    public function test_product_page_has_breadcrumbs_view_variable(): void
    {
        $product = $this->createVisibleProduct();

        $response = $this->get(route('product.show', $product->alias));

        $response->assertViewHas('breadcrumbs');
        $response->assertViewHas('product');
        $response->assertViewHas('category');
    }
}
