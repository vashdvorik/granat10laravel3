<?php

namespace Tests\Feature;

use App\Contracts\AiModerationClient;
use App\Data\ProductModerationPayload;
use App\Data\ProductModerationResult;
use App\Enums\ProductModerationStatus;
use App\Models\Category;
use App\Models\Image;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
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
        $response->assertViewHas('productStatusFilters');
        $response->assertViewHas('currentProductStatus', 'all');
        $response->assertSee('BackendIndexProduct_UNIQ');
        $response->assertSee('seller-products-layout', false);
        $response->assertSee('seller-products-filters', false);
        $response->assertSee('seller-products-main', false);
        $response->assertSee('seller-products-toolbar', false);
        $response->assertSee('seller-product-card__state', false);
        $response->assertSee('Видны покупателям');
        $response->assertSee('Скрыты из-за тарифа');
    }

    public function test_products_index_shows_filtered_empty_state(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        Product::factory()->draft()->create([
            'seller_id' => $seller->id,
            'name' => 'FilteredDraftProduct_UNIQ',
        ]);

        $this->actingAs($user)
            ->get(route('account.store.products.index', ['product_status' => 'visible']))
            ->assertOk()
            ->assertDontSee('FilteredDraftProduct_UNIQ')
            ->assertSee('seller-products-empty', false)
            ->assertSee('uil-filter-slash', false)
            ->assertSee('seller-products-empty__actions', false);
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

    public function test_create_form_replaces_vip_checkbox_with_limit_message_when_limit_reached(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $this->assignActivePackage($seller, vipLimit: 1);
        $category = Category::factory()->create();
        Product::factory()->activeVip()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user)
            ->get(route('account.store.products.create'))
            ->assertOk()
            ->assertSee('Ваш лимит превышен 1/1')
            ->assertDontSee('id="is_vip"', false);
    }

    public function test_edit_form_replaces_vip_checkbox_with_limit_message_when_limit_reached(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $this->assignActivePackage($seller, vipLimit: 1);
        $category = Category::factory()->create();
        Product::factory()->activeVip()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => false,
            'is_vip' => false,
        ]);

        $this->actingAs($user)
            ->get(route('account.store.products.edit', $product))
            ->assertOk()
            ->assertSee('Ваш лимит превышен 1/1')
            ->assertDontSee('id="is_vip"', false);
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

    public function test_create_form_uses_save_action_instead_of_is_active(): void
    {
        [$user] = $this->createUserWithSeller();

        $this->actingAs($user)
            ->get(route('account.store.products.create'))
            ->assertOk()
            ->assertSee('product-form-page', false)
            ->assertSee('product-create-page', false)
            ->assertSee('product-create-form', false)
            ->assertSee('product-form-column', false)
            ->assertSee('product-form-guide', false)
            ->assertSee('product-form-guide__steps', false)
            ->assertDontSee('col-xxl-7 col-lg-10', false)
            ->assertDontSee('mx-sm-30 mx-20', false)
            ->assertSee('name="save_action"', false)
            ->assertDontSee('name="is_active"', false);
    }

    public function test_edit_form_contains_expected_moderation_version(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $this->actingAs($user)
            ->get(route('account.store.products.edit', $product))
            ->assertOk()
            ->assertSee('product-form-page', false)
            ->assertSee('product-edit-page', false)
            ->assertSee('product-edit-form', false)
            ->assertDontSee('product-form-guide--status', false)
            ->assertDontSee('product-form-status', false)
            ->assertDontSee('col-xxl-7 col-lg-10', false)
            ->assertSee('name="expected_moderation_version"', false)
            ->assertSee('value="'.$product->moderation_version.'"', false);
    }

    public function test_stale_edit_form_is_rejected_without_overwriting_product(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $product = Product::factory()->draft()->create(['seller_id' => $seller->id]);
        $originalName = $product->name;

        $this->actingAs($user)
            ->from(route('account.store.products.edit', $product))
            ->put(route('account.store.products.update', $product), [
                'name' => 'Устаревшее изменение',
                'price' => $product->price,
                'category_id' => $product->category_id,
                'save_action' => 'save_draft',
                'expected_moderation_version' => $product->moderation_version + 1,
            ])
            ->assertRedirect(route('account.store.products.edit', $product))
            ->assertSessionHasErrors('product');

        $this->assertSame($originalName, $product->fresh()->name);
    }

    public function test_product_list_uses_state_specific_primary_actions(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        Product::factory()->draft()->create([
            'seller_id' => $seller->id,
            'name' => 'Черновик для кнопки',
        ]);
        Product::factory()->create([
            'seller_id' => $seller->id,
            'name' => 'Товар для исправления',
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'is_active' => false,
            'moderated_version' => null,
        ]);

        $this->actingAs($user)
            ->get(route('account.store.products.index'))
            ->assertOk()
            ->assertSee('Опубликовать')
            ->assertSee('Исправить товар');
    }

    public function test_store_product_returns_validation_error_before_persisting_blocked_title(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $this->assignActivePackage($seller);
        $category = Category::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('account.store.products.create'))
            ->post(route('account.store.products.store'), [
                'name' => 'Ноутбук, звоните +373 777 12345',
                'price' => 1200,
                'category_id' => $category->id,
                'is_active' => 1,
                'is_vip' => 0,
            ]);

        $response
            ->assertRedirect(route('account.store.products.create'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('products', [
            'seller_id' => $seller->id,
            'name' => 'Ноутбук, звоните +373 777 12345',
        ]);
    }

    public function test_sync_provider_failure_does_not_break_product_creation_request(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $this->assignActivePackage($seller);
        $category = Category::factory()->create();
        config()->set('moderation.ai_enabled', true);
        config()->set('moderation.mode', 'auto');
        config()->set('queue.default', 'sync');
        $this->app->bind(AiModerationClient::class, fn () => new class implements AiModerationClient
        {
            public function moderate(ProductModerationPayload $payload): ProductModerationResult
            {
                throw new RuntimeException('Provider unavailable');
            }
        });

        $response = $this->actingAs($user)->post(route('account.store.products.store'), [
            'name' => 'Товар при временно недоступной модерации',
            'price' => 1200,
            'category_id' => $category->id,
            'is_active' => 1,
            'is_vip' => 0,
            'images' => [UploadedFile::fake()->image('product.jpg')],
        ]);

        $response
            ->assertRedirect(route('account.store.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'seller_id' => $seller->id,
            'name' => 'Товар при временно недоступной модерации',
            'moderation_status' => ProductModerationStatus::NeedsReview->value,
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

    public function test_update_can_save_changes_and_submit_product_for_moderation(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $this->assignActivePackage($seller);
        $category = Category::factory()->create();
        $product = Product::factory()->draft()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Original Product',
        ]);
        Image::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($user)->put(
            route('account.store.products.update', $product),
            [
                'name' => 'Updated Product For Moderation',
                'price' => 1500,
                'desc' => 'Подробное описание товара после редактирования.',
                'category_id' => $category->id,
                'is_active' => 1,
                'is_vip' => 0,
            ]
        );

        $response
            ->assertRedirect(route('account.store.products.index'))
            ->assertSessionHas('success');

        $product->refresh();

        $this->assertSame(ProductModerationStatus::Pending, $product->moderation_status);
        $this->assertFalse((bool) $product->is_active);
        $this->assertTrue($product->publication_requested);
        $this->assertSame(2, $product->moderation_version);
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
        $this->assertStringContainsString('Виден покупателям', (string) $response->json('fragments.card_html'));
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
        $this->assignActivePackage($seller);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 1,
            'moderated_version' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->patch(route('account.store.products.deactivate', $product));

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertFalse((bool) $product->fresh()->is_active);
    }

    public function test_owner_can_submit_product_for_moderation(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $this->assignActivePackage($seller);
        $product = Product::factory()->draft()->create([
            'seller_id' => $seller->id,
        ]);
        Image::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($user)
            ->post(route('account.store.products.submit-moderation', $product));

        $response->assertRedirect();
        $this->assertSame(
            ProductModerationStatus::Pending,
            $product->fresh()->moderation_status
        );
    }

    public function test_foreign_user_cannot_submit_product_for_moderation(): void
    {
        $product = Product::factory()->draft()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('account.store.products.submit-moderation', $product))
            ->assertForbidden();
    }

    public function test_push_up_returns_ajax_success_and_cooldown_error(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $this->assignActivePackage($seller);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 1,
            'moderated_version' => 1,
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

    public function test_destroy_image_returns_current_moderation_version(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => false,
            'moderation_version' => 3,
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'moderation_summary' => 'Need photo update',
        ]);
        $image = Image::factory()->main()->create(['product_id' => $product->id]);
        Image::factory()->create(['product_id' => $product->id]);

        $response = $this->actingAs($user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->delete(route('account.store.products.images.destroy', $image));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'current_version' => 4,
            ]);
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

    public function test_product_show_outputs_pushed_design_styles(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($user)
            ->get(route('account.store.products.show', $product))
            ->assertOk()
            ->assertSee('css/seller-cabinet.css', false)
            ->assertDontSee('product-show-overview', false)
            ->assertSee('product-show-card', false)
            ->assertSee('product-show-actions', false);
    }

    private function createUserWithSeller(): array
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        return [$user, $seller];
    }

    private function assignActivePackage(Seller $seller, int $activeLimit = 50, int $vipLimit = 10): void
    {
        $package = Package::factory()->withLimits($activeLimit, $vipLimit)->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);
    }
}
