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

class FavoritesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_favorites_page_returns_200(): void
    {
        $response = $this->get(route('favorites'));

        $response->assertStatus(200);
    }

    public function test_favorites_page_has_modern_empty_state_with_recommendations(): void
    {
        $seller = $this->createSellerWithPackage();
        $category = Category::factory()->create();
        $product = $this->createVisibleProduct($seller, $category, [
            'name' => 'FavoriteRecommendedProduct_UNIQ',
        ]);

        $response = $this->get(route('favorites'));

        $response->assertStatus(200)
            ->assertSee('В избранном пока пусто')
            ->assertSee('Можно начать с этого')
            ->assertSee($product->name)
            ->assertSee('Избранные товары')
            ->assertSee('class="home-product"', false)
            ->assertSee('wishlist:changed')
            ->assertSee('loadFavorites')
            ->assertSee('showProductsState')
            ->assertSee('displayedProductsCount')
            ->assertSee("url: '/favorites/products'", false)
            ->assertDontSee('Ваш список')
            ->assertDontSee('Сохраняйте товары, чтобы быстро вернуться к ним')
            ->assertDontSee('updateCount((favorites || []).length)', false)
            ->assertDontSee('favorites-empty-text');
    }

    public function test_get_products_returns_json_with_html_and_metadata(): void
    {
        $seller = $this->createSellerWithPackage();
        $category = Category::factory()->create();
        $product = $this->createVisibleProduct($seller, $category, [
            'name' => 'FavoriteJsonProduct_UNIQ',
        ]);

        $response = $this->getJson(route('favorites.products', ['ids' => (string) $product->id]));

        $response->assertStatus(200)
            ->assertJson([
                'has_more' => false,
                'total' => 1,
                'last_page' => 1,
            ])
            ->assertSee('FavoriteJsonProduct_UNIQ');
    }

    public function test_get_products_returns_empty_json_for_empty_ids(): void
    {
        $response = $this->getJson(route('favorites.products'));

        $response->assertStatus(200)
            ->assertJson([
                'html' => '',
                'has_more' => false,
                'total' => 0,
                'last_page' => 1,
            ]);
    }

    public function test_get_products_skips_stale_ids_before_visible_products(): void
    {
        $seller = $this->createSellerWithPackage();
        $category = Category::factory()->create();
        $staleProducts = Product::factory()->count(12)->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => false,
        ]);
        $visibleProduct = $this->createVisibleProduct($seller, $category, [
            'name' => 'FavoriteVisibleAfterStale_UNIQ',
        ]);

        $ids = $staleProducts->pluck('id')
            ->push($visibleProduct->id)
            ->implode(',');

        $response = $this->getJson(route('favorites.products', ['ids' => $ids]));

        $response->assertStatus(200)
            ->assertJson([
                'has_more' => false,
                'total' => 1,
                'last_page' => 1,
            ])
            ->assertSee('FavoriteVisibleAfterStale_UNIQ');
    }

    public function test_load_more_favorites_returns_products_partial(): void
    {
        $seller = $this->createSellerWithPackage();
        $category = Category::factory()->create();
        $products = Product::factory()->count(13)->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        $lastProduct = $products->last();
        $lastProduct->update(['name' => 'FavoriteLoadMoreProduct_UNIQ']);

        $response = $this->get(route('favorites.loadMore', [
            'ids' => $products->pluck('id')->implode(','),
            'page' => 2,
        ]));

        $response->assertStatus(200);
        $response->assertSee('FavoriteLoadMoreProduct_UNIQ');
    }

    private function createSellerWithPackage(): Seller
    {
        $seller = Seller::factory()->create();
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
