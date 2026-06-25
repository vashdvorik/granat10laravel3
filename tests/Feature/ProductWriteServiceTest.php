<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Enums\ProductSaveAction;
use App\Models\Category;
use App\Models\Image;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use App\Services\ProductActionService;
use App\Services\ProductModerationIssuePresenter;
use App\Services\ProductWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductWriteServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductWriteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->service = app(ProductWriteService::class);
    }

    public function test_create_sets_owner_alias_flags_and_sale_date(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        Product::factory()->create(['alias' => 'test-product']);

        $product = $this->service->create($seller, [
            'name' => 'Test Product',
            'price' => 1000,
            'lowprice' => 900,
            'category_id' => $category->id,
            'is_active' => false,
            'is_vip' => true,
            'can_order' => true,
            'delivery_enabled' => true,
        ]);

        $this->assertSame($seller->id, $product->seller_id);
        $this->assertSame('test-product-1', $product->alias);
        $this->assertFalse((bool) $product->is_active);
        $this->assertSame(ProductModerationStatus::Draft, $product->moderation_status);
        $this->assertTrue((bool) $product->is_vip);
        $this->assertTrue($product->can_order);
        $this->assertTrue($product->delivery_enabled);
        $this->assertNotNull($product->sale_started_at);
        $this->assertNotNull($product->push_at);
    }

    public function test_active_create_passes_through_local_moderation(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $this->assignActivePackage($seller);

        $product = $this->service->create($seller, [
            'name' => 'Товар для проверки',
            'price' => 100,
            'category_id' => $category->id,
            'desc' => 'Подробное описание товара для прохождения локальной проверки.',
            'is_active' => true,
            'is_vip' => false,
        ], [
            UploadedFile::fake()->image('product.jpg'),
        ], $seller->user);

        $this->assertSame(ProductModerationStatus::Pending, $product->moderation_status);
        $this->assertSame('local_rules', $product->moderation_source);
        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'provider' => 'local',
        ]);
    }

    public function test_create_uses_save_action_without_accepting_publication_flags(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();

        $product = $this->service->create($seller, [
            'name' => 'Новый черновик',
            'price' => 100,
            'category_id' => $category->id,
            'save_action' => ProductSaveAction::Draft->value,
            'is_vip' => false,
        ]);

        $this->assertSame(ProductModerationStatus::Draft, $product->moderation_status);
        $this->assertFalse($product->publication_requested);
        $this->assertFalse($product->is_active);
    }

    public function test_create_draft_without_images_is_allowed(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();

        $product = $this->service->create($seller, [
            'name' => 'Черновик без фото',
            'price' => 100,
            'category_id' => $category->id,
            'save_action' => ProductSaveAction::Draft->value,
            'is_vip' => false,
        ]);

        $this->assertSame(ProductModerationStatus::Draft, $product->moderation_status);
        $this->assertFalse($product->publication_requested);
        $this->assertFalse((bool) $product->is_active);
        $this->assertSame(0, $product->images()->count());
    }

    public function test_create_submit_without_images_is_rejected_with_current_draft_hint(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $this->assignActivePackage($seller);

        try {
            $this->service->create($seller, [
                'name' => 'Товар без фото',
                'price' => 100,
                'category_id' => $category->id,
                'desc' => 'Подробное описание товара для отправки на модерацию.',
                'save_action' => ProductSaveAction::Submit->value,
                'is_vip' => false,
            ], [], $seller->user);

            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('is_active', $exception->errors());
            $this->assertStringContainsString('Нельзя опубликовать товар без изображений', $exception->errors()['is_active'][0]);
            $this->assertStringContainsString('Сохранить черновик', $exception->errors()['is_active'][0]);
        }
    }

    public function test_update_save_action_preserves_publication_intent_for_submission(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $this->assignActivePackage($seller);
        $product = Product::factory()->draft()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'publication_requested' => true,
        ]);
        Image::factory()->create(['product_id' => $product->id]);

        $updated = $this->service->update($product, [
            'name' => 'Обновлённый товар',
            'price' => $product->price,
            'category_id' => $category->id,
            'desc' => 'Подробное описание обновлённого товара для проверки.',
            'save_action' => ProductSaveAction::Submit->value,
            'is_vip' => false,
        ], [], $seller->user);

        $this->assertTrue($updated->publication_requested);
        $this->assertSame(ProductModerationStatus::Pending, $updated->moderation_status);
    }

    public function test_update_draft_without_images_is_allowed(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->draft()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Старый черновик',
        ]);

        $updated = $this->service->update($product, [
            'name' => 'Обновлённый черновик без фото',
            'price' => $product->price,
            'category_id' => $category->id,
            'save_action' => ProductSaveAction::Draft->value,
            'is_vip' => false,
            'expected_moderation_version' => $product->moderation_version,
        ]);

        $this->assertSame('Обновлённый черновик без фото', $updated->name);
        $this->assertSame(ProductModerationStatus::Draft, $updated->moderation_status);
        $this->assertFalse($updated->publication_requested);
        $this->assertFalse((bool) $updated->is_active);
        $this->assertSame(0, $updated->images()->count());
    }

    public function test_update_rejects_stale_form_version(): void
    {
        $product = Product::factory()->draft()->create();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Товар изменился в другой вкладке');

        $this->service->update($product, [
            'name' => $product->name,
            'price' => $product->price,
            'category_id' => $product->category_id,
            'save_action' => ProductSaveAction::Draft->value,
            'expected_moderation_version' => $product->moderation_version + 1,
        ]);
    }

    public function test_safe_save_rejects_moderated_content_change(): void
    {
        $product = Product::factory()->create();

        try {
            $this->service->update($product, [
                'name' => 'Новое название',
                'price' => $product->price,
                'category_id' => $product->category_id,
                'save_action' => ProductSaveAction::Save->value,
                'expected_moderation_version' => $product->moderation_version,
            ]);

            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('save_action', $exception->errors());
            $this->assertNotSame('Новое название', $product->fresh()->name);
        }
    }

    public function test_create_rejects_non_leaf_category_and_remembers_path(): void
    {
        $seller = Seller::factory()->create();
        $root = Category::factory()->create();
        Category::factory()->create(['parent_id' => $root->id]);

        try {
            $this->service->create($seller, [
                'name' => 'Invalid Category Product',
                'price' => 100,
                'category_id' => $root->id,
                'is_active' => false,
            ]);

            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('category_id', $exception->errors());
            $this->assertSame([$root->id], session('selectedPath'));
            $this->assertDatabaseMissing('products', ['name' => 'Invalid Category Product']);
        }
    }

    public function test_update_manages_discount_date_and_boolean_flags(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'lowprice' => null,
            'is_active' => false,
            'is_vip' => false,
        ]);

        $updated = $this->service->update($product, [
            'name' => 'Updated Product',
            'price' => 1000,
            'lowprice' => 800,
            'category_id' => $category->id,
            'is_active' => false,
            'is_vip' => true,
            'can_order' => false,
            'delivery_enabled' => true,
        ]);

        $this->assertSame('Updated Product', $updated->name);
        $this->assertTrue((bool) $updated->is_vip);
        $this->assertFalse($updated->can_order);
        $this->assertFalse($updated->delivery_enabled);
        $this->assertNotNull($updated->sale_started_at);

        $updated = $this->service->update($updated, [
            'name' => 'Updated Product',
            'price' => 1000,
            'lowprice' => null,
            'category_id' => $category->id,
            'is_active' => false,
            'is_vip' => false,
        ]);

        $this->assertNull($updated->sale_started_at);
        $this->assertFalse((bool) $updated->is_vip);
    }

    public function test_update_rejects_image_overflow_before_product_update(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Original Product Name',
            'is_active' => false,
        ]);
        Image::factory()->count(5)->create(['product_id' => $product->id]);

        try {
            $this->service->update($product, [
                'name' => 'Should Not Be Saved',
                'price' => 1000,
                'lowprice' => null,
                'category_id' => $category->id,
                'is_active' => false,
                'is_vip' => false,
            ], [
                UploadedFile::fake()->create('extra.jpg', 10, 'image/jpeg'),
            ]);

            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('images', $exception->errors());
            $this->assertSame('Original Product Name', $product->fresh()->name);
            $this->assertCount(5, $product->fresh()->images);
        }
    }

    public function test_unchanged_save_after_switching_main_image_preserves_publication(): void
    {
        $seller = Seller::factory()->create();
        $this->assignActivePackage($seller);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
            'publication_requested' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 2,
            'moderated_version' => 2,
        ]);
        Image::factory()->main()->create(['product_id' => $product->id]);
        $newMain = Image::factory()->create(['product_id' => $product->id]);
        app(ProductActionService::class)->setMainImage($newMain);

        $updated = $this->service->update($product, [
            'name' => $product->name,
            'price' => $product->price,
            'lowprice' => $product->lowprice,
            'desc' => $product->desc,
            'features1' => $product->features1,
            'features2' => $product->features2,
            'features3' => $product->features3,
            'features4' => $product->features4,
            'category_id' => $product->category_id,
            'is_vip' => $product->is_vip,
            'can_order' => $product->can_order,
            'delivery_enabled' => $product->delivery_enabled,
            'save_action' => ProductSaveAction::Save->value,
        ]);

        $this->assertTrue((bool) $updated->is_active);
        $this->assertSame(ProductModerationStatus::Approved, $updated->moderation_status);
        $this->assertSame(2, $updated->moderated_version);
    }

    public function test_replacing_photo_increments_version_and_hides_old_image_issue(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $this->assignActivePackage($seller);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'moderation_version' => 2,
            'moderation_summary' => 'Замените фото.',
        ]);
        Image::factory()->create(['product_id' => $product->id]);
        ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => 2,
            'trigger' => 'image_queue',
            'provider' => 'image_google',
            'status' => 'completed',
            'decision' => 'review',
            'request_fingerprint' => hash('sha256', 'old-image-issue'),
            'reasons' => [[
                'field' => 'images',
                'image_index' => 1,
                'evidence' => 'Старое фото не подходит.',
                'suggestion' => 'Замените фото.',
            ]],
        ]);

        $updated = $this->service->update($product, [
            'name' => $product->name,
            'price' => $product->price,
            'lowprice' => $product->lowprice,
            'desc' => $product->desc,
            'features1' => $product->features1,
            'features2' => $product->features2,
            'features3' => $product->features3,
            'features4' => $product->features4,
            'category_id' => $product->category_id,
            'is_vip' => $product->is_vip,
            'can_order' => $product->can_order,
            'delivery_enabled' => $product->delivery_enabled,
            'save_action' => ProductSaveAction::Submit->value,
            'expected_moderation_version' => 2,
        ], [
            UploadedFile::fake()->image('replacement.jpg'),
        ], $seller->user);

        $this->assertSame(3, $updated->moderation_version);
        $this->assertSame(ProductModerationStatus::Pending, $updated->moderation_status);
        $this->assertNull($updated->moderation_summary);
        $this->assertSame([], app(ProductModerationIssuePresenter::class)->forProduct($updated));
    }

    public function test_moderation_uses_explicit_actor_instead_of_authenticated_user(): void
    {
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $this->assignActivePackage($seller);
        $explicitActor = $seller->user;
        $this->actingAs(User::factory()->create());

        $product = $this->service->create($seller, [
            'name' => 'Товар с явным автором',
            'price' => 100,
            'category_id' => $category->id,
            'desc' => 'Подробное описание товара для автоматической проверки.',
            'is_active' => true,
            'is_vip' => false,
        ], [
            UploadedFile::fake()->image('product.jpg'),
        ], $explicitActor);

        $this->assertDatabaseHas('product_moderation_actions', [
            'product_id' => $product->id,
            'actor_user_id' => $explicitActor->id,
            'action' => 'submitted',
        ]);
    }

    private function assignActivePackage(Seller $seller): void
    {
        $package = Package::factory()->withLimits(10, 5)->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => now()->addMonth(),
        ]);
    }
}
