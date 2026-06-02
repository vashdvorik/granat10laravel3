<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_catalog_page(): void
    {
        $response = $this->get(route('catalog'));

        $response->assertStatus(200);
        $response->assertViewIs('catalog.index');
        $response->assertViewHas('products');
        $response->assertViewHas('categories');
    }

    public function test_catalog_displays_visible_products(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        // Создаем активный продукт
        $activeProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        // Создаем неактивный продукт
        $inactiveProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        $response = $this->get(route('catalog'));

        $response->assertStatus(200);
        $response->assertSee($activeProduct->name);
        $response->assertDontSee($inactiveProduct->name);
    }

    public function test_can_filter_catalog_by_seller(): void
    {
        $user1 = User::factory()->create();
        $seller1 = Seller::factory()->create(['user_id' => $user1->id, 'slug' => 'seller-one']);

        $user2 = User::factory()->create();
        $seller2 = Seller::factory()->create(['user_id' => $user2->id, 'slug' => 'seller-two']);

        $product1 = Product::factory()->create([
            'seller_id' => $seller1->id,
            'user_id' => $user1->id,
            'is_active' => true,
            'name' => 'Product from Seller 1',
        ]);

        $product2 = Product::factory()->create([
            'seller_id' => $seller2->id,
            'user_id' => $user2->id,
            'is_active' => true,
            'name' => 'Product from Seller 2',
        ]);

        $response = $this->get(route('catalog', ['seller' => 'seller-one']));

        $response->assertStatus(200);
        $response->assertSee('Product from Seller 1');
        $response->assertDontSee('Product from Seller 2');
    }

    public function test_can_view_category_page(): void
    {
        $category = Category::factory()->create([
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $response = $this->get(route('catalog.show', ['alias' => 'test-category']));

        $response->assertStatus(200);
        $response->assertViewIs('catalog.category');
        $response->assertViewHas('category');
        $response->assertViewHas('breadcrumbs');
    }

    public function test_category_page_returns_404_for_inactive_category(): void
    {
        $category = Category::factory()->create([
            'slug' => 'inactive-category',
            'is_active' => false,
        ]);

        $response = $this->get(route('catalog.show', ['alias' => 'inactive-category']));

        $response->assertStatus(404);
    }

    public function test_can_search_products(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
            'name' => 'Unique Search Term Product',
        ]);

        $response = $this->get(route('catalog.search', ['q' => 'Unique Search Term']));

        $response->assertStatus(200);
        $response->assertViewIs('catalog.search');
        $response->assertSee('Unique Search Term Product');
    }

    public function test_search_requires_minimum_3_characters(): void
    {
        $response = $this->get(route('catalog.search', ['q' => 'ab']));

        $response->assertRedirect(route('catalog'));
        $response->assertSessionHas('error');
    }

    public function test_catalog_displays_categories_tree(): void
    {
        $parentCategory = Category::factory()->create([
            'name' => 'Parent Category',
            'is_active' => true,
            'parent_id' => null,
        ]);

        $childCategory = Category::factory()->create([
            'name' => 'Child Category',
            'is_active' => true,
            'parent_id' => $parentCategory->id,
        ]);

        $response = $this->get(route('catalog'));

        $response->assertStatus(200);
        $response->assertSee('Parent Category');
        $response->assertSee('Child Category');
    }

    public function test_catalog_pagination_works(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        // Создаем 15 продуктов (больше чем 12 на странице)
        Product::factory()->count(15)->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('catalog'));

        $response->assertStatus(200);
        $response->assertSee('pagination');
    }
}
