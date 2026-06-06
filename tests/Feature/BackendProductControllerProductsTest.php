<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Image;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackendProductControllerProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('public');
    }

    public function test_products_index_returns_service_view_data(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $category = Category::factory()->create();
        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'BackendIndexProduct_UNIQ',
        ]);

        $response = $this->actingAs($user)->get(route('account.store.products.index'));

        $response->assertStatus(200);
        $response->assertViewHas('products');
        $response->assertViewHas('categoryTree');
        $response->assertViewHas('sellerCategoryIds');
        $response->assertSee('BackendIndexProduct_UNIQ');
    }

    public function test_products_create_returns_root_categories(): void
    {
        [$user] = $this->createUserWithSeller();
        Category::factory()->create(['parent_id' => null, 'name' => 'RootCategory_UNIQ']);

        $response = $this->actingAs($user)->get(route('account.store.products.create'));

        $response->assertStatus(200);
        $response->assertViewHas('categories');
        $response->assertSee('RootCategory_UNIQ');
    }

    public function test_get_child_categories_returns_json(): void
    {
        [$user] = $this->createUserWithSeller();
        $root = Category::factory()->create();
        $child = Category::factory()->create([
            'parent_id' => $root->id,
            'name' => 'ChildCategory_UNIQ',
        ]);

        $response = $this->actingAs($user)->getJson(route('account.categories.children', [
            'parent_id' => $root->id,
        ]));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $child->id,
                'name' => 'ChildCategory_UNIQ',
                'parent_id' => $root->id,
                'has_children' => false,
            ]);
    }

    public function test_store_product_creates_inactive_product(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)->post(route('account.store.products.store'), [
            'name' => 'Stored Backend Product',
            'price' => 1200,
            'category_id' => $category->id,
            'is_active' => 0,
            'is_vip' => 0,
        ]);

        $response->assertRedirect(route('account.store.products.index'));
        $this->assertDatabaseHas('products', [
            'seller_id' => $seller->id,
            'name' => 'Stored Backend Product',
            'is_active' => false,
        ]);
    }

    public function test_update_product_uses_write_service(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->put(route('account.store.products.update', $product), [
            'name' => 'Updated Backend Product',
            'price' => 1500,
            'category_id' => $category->id,
            'is_active' => 0,
            'is_vip' => 1,
        ]);

        $response->assertRedirect(route('account.store.products.index'));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Backend Product',
            'is_vip' => true,
        ]);
    }

    public function test_activate_product_returns_ajax_success(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $this->assignActivePackage($seller);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => false,
        ]);
        Image::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->patch(route('account.store.products.activate', $product));

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertTrue((bool) $product->fresh()->is_active);
    }

    public function test_activate_product_returns_ajax_error_without_images(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $this->assignActivePackage($seller);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->patch(route('account.store.products.activate', $product));

        $response->assertStatus(422)->assertJsonStructure(['error']);
        $this->assertFalse((bool) $product->fresh()->is_active);
    }

    public function test_deactivate_product_returns_ajax_success(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->patch(route('account.store.products.deactivate', $product));

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertFalse((bool) $product->fresh()->is_active);
    }

    public function test_push_up_returns_ajax_success_and_cooldown_error(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'push_at' => now()->subHours(25),
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('account.store.products.push-up', $product));

        $response->assertOk()->assertJsonStructure([
            'success',
            'push_at',
            'next_available_at',
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('account.store.products.push-up', $product));

        $response->assertStatus(422)->assertJsonStructure([
            'error',
            'available_at',
        ]);
    }

    public function test_destroy_product_removes_product_and_image_file(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ]);
        $image = Image::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/controller-delete.webp',
        ]);
        Storage::disk('public')->put($image->path, 'image');

        $response = $this->actingAs($user)
            ->delete(route('account.store.products.destroy', $product));

        $response->assertRedirect(route('account.store.products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_destroy_last_image_of_active_product_returns_ajax_error(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        $image = Image::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->delete(route('account.store.products.images.destroy', $image));

        $response->assertStatus(422)->assertJsonStructure(['error']);
        $this->assertDatabaseHas('images', ['id' => $image->id]);
    }

    public function test_set_main_image_returns_ajax_success(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ]);
        $oldMain = Image::factory()->main()->create(['product_id' => $product->id]);
        $newMain = Image::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->patch(route('account.store.products.images.set-main', $newMain));

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertFalse((bool) $oldMain->fresh()->is_main);
        $this->assertTrue((bool) $newMain->fresh()->is_main);
    }

    private function createUserWithSeller(): array
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        return [$user, $seller];
    }

    private function assignActivePackage(Seller $seller): void
    {
        $package = Package::factory()->withLimits(50, 10)->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);
    }
}
