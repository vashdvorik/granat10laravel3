<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Seller $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->seller = Seller::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_can_view_products_index(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('account.store.products.index'));

        $response->assertStatus(200);
        $response->assertViewIs('backend.cabinet.store.products');
        $response->assertViewHas('products');
        $response->assertViewHas('seller');
    }

    public function test_cannot_view_products_without_seller(): void
    {
        $userWithoutSeller = User::factory()->create();
        $this->actingAs($userWithoutSeller);

        $response = $this->get(route('account.store.products.index'));

        $response->assertRedirect(route('account.store.create'));
        $response->assertSessionHas('error');
    }

    public function test_can_view_create_product_form(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('account.store.products.create'));

        $response->assertStatus(200);
        $response->assertViewIs('backend.cabinet.products.create');
        $response->assertViewHas('categories');
    }

    public function test_can_create_product(): void
    {
        $this->actingAs($this->user);

        $category = Category::factory()->create();

        $data = [
            'name' => 'Test Product',
            'description' => 'This is a test product description that is long enough for validation',
            'price' => 1500.00,
            'category_id' => $category->id,
            'is_active' => false, // Неактивный, чтобы не проверять лимиты
        ];

        $response = $this->post(route('account.store.products.store'), $data);

        $response->assertRedirect(route('account.store.products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'price' => 1500.00,
            'seller_id' => $this->seller->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_cannot_create_product_with_invalid_data(): void
    {
        $this->actingAs($this->user);

        $data = [
            'name' => '', // Пустое имя - невалидно
            'price' => -100, // Отрицательная цена - невалидно
        ];

        $response = $this->post(route('account.store.products.store'), $data);

        $response->assertSessionHasErrors(['name', 'price', 'category_id']);
    }

    public function test_can_view_edit_product_form(): void
    {
        $this->actingAs($this->user);

        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('account.store.products.edit', $product));

        $response->assertStatus(200);
        $response->assertViewIs('backend.cabinet.products.edit');
        $response->assertViewHas('product');
        $response->assertViewHas('categories');
    }

    public function test_cannot_edit_other_user_product(): void
    {
        $otherUser = User::factory()->create();
        $otherSeller = Seller::factory()->create(['user_id' => $otherUser->id]);
        $product = Product::factory()->create([
            'seller_id' => $otherSeller->id,
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->get(route('account.store.products.edit', $product));

        $response->assertStatus(403);
    }

    public function test_can_update_product(): void
    {
        $this->actingAs($this->user);

        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'user_id' => $this->user->id,
        ]);

        $data = [
            'name' => 'Updated Product Name',
            'price' => 2500.00,
        ];

        $response = $this->put(route('account.store.products.update', $product), $data);

        $response->assertRedirect(route('account.store.products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => 2500.00,
        ]);
    }

    public function test_can_delete_product(): void
    {
        $this->actingAs($this->user);

        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->delete(route('account.store.products.destroy', $product));

        $response->assertRedirect(route('account.store.products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_cannot_delete_other_user_product(): void
    {
        $otherUser = User::factory()->create();
        $otherSeller = Seller::factory()->create(['user_id' => $otherUser->id]);
        $product = Product::factory()->create([
            'seller_id' => $otherSeller->id,
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->delete(route('account.store.products.destroy', $product));

        $response->assertStatus(403);
    }
}
