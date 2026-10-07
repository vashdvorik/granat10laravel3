<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        $seller   = Seller::factory()->create();
        $category = Category::factory()->create();


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

    public function test_product_detail_increments_views_counter(): void
    {
        $product = $this->createVisibleProduct();

        $this->get(route('product.show', $product->alias))->assertStatus(200);
        $this->get(route('product.show', $product->alias))->assertStatus(200);

        $this->assertSame(2, DB::table('views')->where('product_id', $product->id)->value('views'));
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

    public function test_product_detail_is_visible_for_seller_without_any_package(): void
    {
        $product = $this->createVisibleProduct();

        $this->get(route('product.show', $product->alias))->assertOk();
    }

    public function test_product_detail_returns_404_when_moderation_is_not_approved(): void
    {
        $seller   = Seller::factory()->create();
        $category = Category::factory()->create();

        $product = Product::factory()->draft()->create([
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
        $response->assertViewHas('relatedProducts');
    }

    public function test_product_page_has_real_seo_markup_without_demo_content(): void
    {
        $product = $this->createVisibleProduct(['name' => 'Товар для SEO']);

        $response = $this->get(route('product.show', $product->alias));

        $response
            ->assertOk()
            ->assertSee('"@type":"Product"', false)
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertDontSee('896 reviews')
            ->assertDontSee('Customer questions & answers')
            ->assertDontSee('Cordless TrackMan Wheel');

        $this->assertSame(1, substr_count($response->getContent(), 'rel="canonical"'));
        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_product_page_shows_other_products_from_same_seller_as_fallback(): void
    {
        $product = $this->createVisibleProduct();
        $related = Product::factory()->create([
            'seller_id' => $product->seller_id,
            'category_id' => Category::factory()->create()->id,
            'name' => 'Другой товар этого продавца',
            'is_active' => true,
        ]);

        $this->get(route('product.show', $product->alias))
            ->assertOk()
            ->assertSee('Другие товары продавца')
            ->assertSee($related->name)
            ->assertSee('class="home-product"', false)
            ->assertDontSee('class="related-card"', false);
    }

    public function test_product_gallery_uses_local_switcher_for_multiple_images(): void
    {
        $product = $this->createVisibleProduct();

        Image::factory()->main()->create([
            'product_id' => $product->id,
            'path' => 'products/main.jpg',
            'order' => 1,
        ]);
        Image::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/second.jpg',
            'order' => 2,
        ]);

        $response = $this->get(route('product.show', $product->alias));

        $response->assertOk()
            ->assertSee('data-product-gallery', false)
            ->assertSee('data-product-gallery-thumb', false)
            ->assertSee('data-gallery-index="1"', false)
            ->assertSee('showSlide(index)', false)
            ->assertDontSee('js-product-slider')
            ->assertDontSee('js-click-product');
    }

    public function test_product_gallery_shows_placeholder_when_product_has_no_images(): void
    {
        $product = $this->createVisibleProduct();

        $response = $this->get(route('product.show', $product->alias));

        $response->assertOk()
            ->assertSee('class="product-gallery__slide active"', false)
            ->assertSee('data-product-gallery-slide', false)
            ->assertSee('src="' . asset('img/no-image.jpg') . '"', false);
    }

    public function test_product_summary_elements_are_aligned(): void
    {
        $product = $this->createVisibleProduct();

        $response = $this->get(route('product.show', $product->alias));

        $response->assertOk()
            ->assertSee('.product-summary__heading{display:flex;align-items:center;gap:18px}', false)
            ->assertSee('.product-favorite{display:flex;align-items:center;justify-content:center;', false)
            ->assertSee('.product-summary .product-price{display:flex;align-items:center;flex-wrap:wrap;', false)
            ->assertSee('.product-meta>div{display:grid;grid-template-columns:92px minmax(0,1fr);align-items:center;', false)
            ->assertSee('.product-meta dd{display:flex;align-items:center;gap:8px;', false);
    }

    public function test_product_order_form_uses_full_width_section(): void
    {
        $product = $this->createVisibleProduct(['can_order' => true]);
        $product->seller->update(['can_order' => true]);

        $this->get(route('product.show', $product->alias))
            ->assertOk()
            ->assertSee('class="product-order-section"', false)
            ->assertSee('class="order-form" id="order-form"', false)
            ->assertSee('.product-order-section{width:100%;margin:50px 0 0}', false)
            ->assertDontSee('.product-order-section{max-width:820px;margin:50px auto 0}', false);
    }

    public function test_product_order_form_shows_seller_delivery_terms(): void
    {
        $product = $this->createVisibleProduct([
            'can_order' => true,
            'delivery_enabled' => true,
        ]);
        $product->seller->update([
            'can_order' => true,
            'delivery_enabled' => true,
            'delivery_terms' => "Доставка по городу.\nСтоимость согласовывается с продавцом.",
        ]);

        $this->get(route('product.show', $product->alias))
            ->assertOk()
            ->assertSee('Условия доставки')
            ->assertSee('Доставка по городу.')
            ->assertSee('Стоимость согласовывается с продавцом.');
    }

    public function test_product_mobile_call_action_shows_phone_number(): void
    {
        $product = $this->createVisibleProduct();
        $product->seller->update(['phone' => '+373 777 12 345']);

        $response = $this->get(route('product.show', $product->alias));

        $response->assertOk()
            ->assertSee('class="product-mobile-actions__call"', false)
            ->assertSee('href="tel:+37377712345"', false)
            ->assertSee('+373 777 12 345')
            ->assertSee('aria-label="Позвонить продавцу +373 777 12 345"', false);
    }
}
