<?php

namespace Tests\Feature;

use App\Models\Image as ProductImage;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductImageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->service = new ProductImageService();
    }

    public function test_store_uploaded_images_creates_records_and_files(): void
    {
        $product = Product::factory()->create();

        $this->service->storeUploadedImages($product, [
            UploadedFile::fake()->image('first.jpg', 1000, 1000),
            UploadedFile::fake()->image('second.png', 1000, 1000),
        ], true);

        $images = $product->images()->orderBy('id')->get();

        $this->assertCount(2, $images);
        $this->assertTrue((bool) $images[0]->is_main);
        $this->assertFalse((bool) $images[1]->is_main);
        $this->assertStringEndsWith('.webp', $images[0]->path);
        Storage::disk('public')->assertExists($images[0]->path);
        Storage::disk('public')->assertExists($images[1]->path);
    }

    public function test_can_add_images_respects_limit(): void
    {
        $product = Product::factory()->create();
        ProductImage::factory()->count(4)->create(['product_id' => $product->id]);

        $this->assertTrue($this->service->canAddImages($product, 1));
        $this->assertFalse($this->service->canAddImages($product, 2));
        $this->assertSame(1, $this->service->remainingImageSlots($product));
    }

    public function test_delete_main_image_removes_file_and_promotes_next_image(): void
    {
        $product = Product::factory()->create();
        $mainImage = ProductImage::factory()->main()->create([
            'product_id' => $product->id,
            'path' => 'products/main.webp',
        ]);
        $nextImage = ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/next.webp',
        ]);
        Storage::disk('public')->put($mainImage->path, 'main');
        Storage::disk('public')->put($nextImage->path, 'next');

        $this->service->deleteImage($mainImage);

        Storage::disk('public')->assertMissing('products/main.webp');
        Storage::disk('public')->assertExists('products/next.webp');
        $this->assertDatabaseMissing('images', ['id' => $mainImage->id]);
        $this->assertTrue((bool) $nextImage->fresh()->is_main);
    }

    public function test_set_main_image_resets_other_images(): void
    {
        $product = Product::factory()->create();
        $oldMain = ProductImage::factory()->main()->create(['product_id' => $product->id]);
        $newMain = ProductImage::factory()->create(['product_id' => $product->id]);

        $this->service->setMainImage($newMain);

        $this->assertFalse((bool) $oldMain->fresh()->is_main);
        $this->assertTrue((bool) $newMain->fresh()->is_main);
    }
}
