<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Services\FavoritesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FavoritesServiceTest extends TestCase
{
    use RefreshDatabase;

    private FavoritesService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->service = new FavoritesService();
    }

    public function test_parse_ids_sanitizes_and_limits_values(): void
    {
        $rawIds = implode(',', array_merge(['abc', '0', '-3', '10', '10'], range(1, 250)));

        $ids = $this->service->parseIds($rawIds);

        $this->assertCount(FavoritesService::MAX_IDS, $ids);
        $this->assertSame([-3, 10, 1, 2], array_slice($ids, 0, 4));
    }

    public function test_initial_products_returns_metadata_and_only_first_page(): void
    {
        $seller = $this->createSeller();
        $category = Category::factory()->create();
        $products = Product::factory()->count(13)->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $result = $this->service->getInitialProducts($products->pluck('id')->implode(','));

        $this->assertCount(FavoritesService::PER_PAGE, $result['products']);
        $this->assertTrue($result['has_more']);
        $this->assertSame(13, $result['total']);
        $this->assertSame(2, $result['last_page']);
    }

    public function test_products_page_returns_visible_products_in_requested_order(): void
    {
        $seller = $this->createSeller();
        $category = Category::factory()->create();
        $first = $this->createVisibleProduct($seller, $category, ['name' => 'First Favorite']);
        $second = $this->createVisibleProduct($seller, $category, ['name' => 'Second Favorite']);
        $hidden = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $products = $this->service->getProductsPage($second->id . ',' . $hidden->id . ',' . $first->id, 1);

        $this->assertSame([$second->id, $first->id], $products->pluck('id')->all());
    }

    public function test_products_page_uses_requested_page_offset(): void
    {
        $seller = $this->createSeller();
        $category = Category::factory()->create();
        $products = Product::factory()->count(13)->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $pageProducts = $this->service->getProductsPage($products->pluck('id')->implode(','), 2);

        $this->assertCount(1, $pageProducts);
        $this->assertSame($products->last()->id, $pageProducts->first()->id);
    }

    private function createSeller(): Seller
    {
        $seller = Seller::factory()->create();


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
