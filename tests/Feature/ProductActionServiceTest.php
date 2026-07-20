<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Exceptions\ProductActivationException;
use App\Exceptions\ProductImageDeletionException;
use App\Exceptions\ProductPushTooSoonException;
use App\Models\Category;
use App\Models\Image;
use App\Models\Package;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Services\ProductActionService;
use App\Services\ProductModerationIssuePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductActionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductActionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('public');
        $this->service = app(ProductActionService::class);
    }

    public function test_activate_rejects_product_without_images(): void
    {
        $product = $this->createProductWithActivePackage(['is_active' => false]);

        $this->expectException(ProductActivationException::class);

        try {
            $this->service->activate($product);
        } finally {
            $this->assertFalse((bool) $product->fresh()->is_active);
        }
    }

    public function test_activate_publishes_product_when_requirements_are_met(): void
    {
        $product = $this->createProductWithActivePackage(['is_active' => false]);
        Image::factory()->create(['product_id' => $product->id]);

        $activated = $this->service->activate($product);

        $this->assertTrue((bool) $activated->is_active);
    }

    public function test_activate_rejects_product_without_current_moderation_approval(): void
    {
        $product = $this->createProductWithActivePackage([
            'is_active' => false,
            'moderation_status' => ProductModerationStatus::Pending,
            'moderated_version' => null,
        ]);
        Image::factory()->create(['product_id' => $product->id]);

        $this->expectException(ProductActivationException::class);

        try {
            $this->service->activate($product);
        } finally {
            $this->assertFalse((bool) $product->fresh()->is_active);
        }
    }

    public function test_deactivate_hides_product(): void
    {
        $product = $this->createProductWithActivePackage(['is_active' => true]);

        $deactivated = $this->service->deactivate($product);

        $this->assertFalse((bool) $deactivated->is_active);
    }

    public function test_delete_removes_product_images_and_files(): void
    {
        $product = $this->createProductWithActivePackage();
        $image = Image::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/delete-product.webp',
        ]);
        Storage::disk('public')->put($image->path, 'image');

        $this->service->delete($product);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_delete_image_rejects_last_image_of_active_product(): void
    {
        $product = $this->createProductWithActivePackage(['is_active' => true]);
        $image = Image::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/last-active.webp',
        ]);
        Storage::disk('public')->put($image->path, 'image');

        $this->expectException(ProductImageDeletionException::class);

        try {
            $this->service->deleteImage($image);
        } finally {
            $this->assertDatabaseHas('images', ['id' => $image->id]);
            Storage::disk('public')->assertExists($image->path);
        }
    }

    public function test_delete_image_removes_allowed_image_and_promotes_next(): void
    {
        $product = $this->createProductWithActivePackage(['is_active' => true]);
        $mainImage = Image::factory()->main()->create([
            'product_id' => $product->id,
            'path' => 'products/main-action.webp',
        ]);
        $nextImage = Image::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/next-action.webp',
        ]);
        Storage::disk('public')->put($mainImage->path, 'main');
        Storage::disk('public')->put($nextImage->path, 'next');

        $updatedProduct = $this->service->deleteImage($mainImage);

        $this->assertDatabaseMissing('images', ['id' => $mainImage->id]);
        $this->assertTrue((bool) $nextImage->fresh()->is_main);
        $this->assertSame($product->fresh()->moderation_version, $updatedProduct->moderation_version);
        Storage::disk('public')->assertMissing($mainImage->path);
    }

    public function test_delete_image_increments_version_and_hides_old_image_issue(): void
    {
        $product = $this->createProductWithActivePackage([
            'is_active' => false,
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'moderation_version' => 4,
            'moderation_summary' => 'Замените фото.',
        ]);
        $image = Image::factory()->main()->create([
            'product_id' => $product->id,
            'path' => 'products/problem-action.webp',
        ]);
        $nextImage = Image::factory()->create(['product_id' => $product->id]);
        Storage::disk('public')->put($image->path, 'problem');
        ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => 4,
            'trigger' => 'image_queue',
            'provider' => 'image_google',
            'status' => 'completed',
            'decision' => 'review',
            'request_fingerprint' => hash('sha256', 'delete-image-issue'),
            'reasons' => [[
                'field' => 'images',
                'image_index' => 1,
                'evidence' => 'Старое фото не подходит.',
                'suggestion' => 'Удалите фото.',
            ]],
        ]);

        $updatedProduct = $this->service->deleteImage($image);

        $product->refresh();
        $this->assertSame(5, $product->moderation_version);
        $this->assertSame(5, $updatedProduct->moderation_version);
        $this->assertSame(ProductModerationStatus::Draft, $product->moderation_status);
        $this->assertNull($product->moderation_summary);
        $this->assertSame([], app(ProductModerationIssuePresenter::class)->forProduct($product));
        $this->assertTrue((bool) $nextImage->fresh()->is_main);
    }

    public function test_set_main_image_switches_main_flag(): void
    {
        $product = $this->createProductWithActivePackage();
        $oldMain = Image::factory()->main()->create(['product_id' => $product->id]);
        $newMain = Image::factory()->create(['product_id' => $product->id]);

        $this->service->setMainImage($newMain);

        $this->assertFalse((bool) $oldMain->fresh()->is_main);
        $this->assertTrue((bool) $newMain->fresh()->is_main);
    }

    public function test_set_main_image_preserves_current_approval(): void
    {
        $product = $this->createProductWithActivePackage([
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 2,
            'moderated_version' => 2,
        ]);
        Image::factory()->main()->create(['product_id' => $product->id]);
        $newMain = Image::factory()->create(['product_id' => $product->id]);

        $this->service->setMainImage($newMain);

        $product->refresh();
        $this->assertSame(ProductModerationStatus::Approved, $product->moderation_status);
        $this->assertSame(2, $product->moderation_version);
        $this->assertSame(2, $product->moderated_version);
        $this->assertTrue((bool) $product->is_active);
    }

    public function test_push_up_rejects_product_before_24_hours(): void
    {
        $product = $this->createProductWithActivePackage([
            'push_at' => now()->subHours(2),
        ]);

        try {
            $this->service->pushUp($product);
            $this->fail('ProductPushTooSoonException was not thrown.');
        } catch (ProductPushTooSoonException $exception) {
            $this->assertTrue($exception->availableAt->isFuture());
            $this->assertSame(
                $product->push_at->copy()->addHours(24)->toIso8601String(),
                $exception->availableAt->toIso8601String()
            );
        }
    }

    public function test_push_up_updates_timestamp_and_returns_next_available_time(): void
    {
        $product = $this->createProductWithActivePackage([
            'push_at' => now()->subHours(25),
        ]);

        $result = $this->service->pushUp($product);

        $this->assertTrue($result['product']->push_at->equalTo($result['pushed_at']));
        $this->assertTrue($result['next_available_at']->equalTo($result['pushed_at']->copy()->addHours(24)));
    }

    private function createProductWithActivePackage(array $productAttributes = []): Product
    {
        $seller = Seller::factory()->create();
        $package = Package::factory()->withLimits(50, 10)->create();
        $category = Category::factory()->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);

        return Product::factory()->create(array_merge([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ], $productAttributes));
    }
}
