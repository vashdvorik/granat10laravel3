<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_product_page(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_active' => true,
            'alias' => 'test-product',
        ]);

        $response = $this->get(route('product.show', ['alias' => 'test-product']));

        $response->assertStatus(200);
        $response->assertViewIs('product');
        $response->assertViewHas('product');
        $response->assertSee($product->name);
    }

    public function test_inactive_product_returns_404(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => false,
            'alias' => 'inactive-product',
        ]);

        $response = $this->get(route('product.show', ['alias' => 'inactive-product']));

        $response->assertStatus(404);
    }

    public function test_product_page_displays_breadcrumbs(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $parentCategory = Category::factory()->create([
            'name' => 'Electronics',
            'parent_id' => null,
        ]);

        $childCategory = Category::factory()->create([
            'name' => 'Smartphones',
            'parent_id' => $parentCategory->id,
        ]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'category_id' => $childCategory->id,
            'is_active' => true,
            'alias' => 'test-smartphone',
        ]);

        $response = $this->get(route('product.show', ['alias' => 'test-smartphone']));

        $response->assertStatus(200);
        $response->assertViewHas('breadcrumbs');
        $response->assertSee('Electronics');
        $response->assertSee('Smartphones');
    }

    public function test_product_page_displays_seller_info(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Shop',
        ]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
            'alias' => 'test-product',
        ]);

        $response = $this->get(route('product.show', ['alias' => 'test-product']));

        $response->assertStatus(200);
        $response->assertSee('Test Shop');
    }

    public function test_viewing_product_increments_view_count(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
            'alias' => 'test-product',
        ]);

        $initialViews = $product->views_count ?? 0;

        $this->get(route('product.show', ['alias' => 'test-product']));

        // Проверяем что счетчик увеличился
        $product->refresh();
        $this->assertGreaterThan($initialViews, $product->views_count);
    }

    public function test_product_page_displays_similar_products(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $category = Category::factory()->create();

        $mainProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_active' => true,
            'alias' => 'main-product',
            'name' => 'Main Product',
        ]);

        // Создаем похожие продукты в той же категории
        Product::factory()->count(3)->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('product.show', ['alias' => 'main-product']));

        $response->assertStatus(200);
        $response->assertViewHas('similarProducts');
    }

    public function test_product_not_found_returns_404(): void
    {
        $response = $this->get(route('product.show', ['alias' => 'non-existent-product']));

        $response->assertStatus(404);
    }

    public function test_owner_can_view_inactive_product(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => false,
            'alias' => 'owner-product',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('product.show', ['alias' => 'owner-product']));

        // Владелец может видеть свой неактивный товар
        $response->assertStatus(200);
    }

    public function test_product_page_displays_price(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
            'alias' => 'priced-product',
            'price' => 1500.00,
        ]);

        $response = $this->get(route('product.show', ['alias' => 'priced-product']));

        $response->assertStatus(200);
        $response->assertSee('1500');
    }
}
