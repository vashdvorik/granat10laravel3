<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Image;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImageService $imageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->imageService = app(ImageService::class);
        Storage::fake('public');
    }

    public function test_can_upload_image(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $imagePath = $this->imageService->uploadImage($file, 'public');

        $this->assertNotNull($imagePath);
        $this->assertStringContainsString('images/', $imagePath);
        Storage::disk('public')->assertExists($imagePath);
    }

    public function test_upload_creates_thumbnail(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $imagePath = $this->imageService->uploadImage($file, 'public');

        // Проверяем, что создана миниатюра
        $thumbnailPath = str_replace('images/', 'images/thumbnails/', $imagePath);
        Storage::disk('public')->assertExists($thumbnailPath);
    }

    public function test_upload_creates_resized_version(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 2000, 1500);

        $imagePath = $this->imageService->uploadImage($file, 'public');

        // Проверяем, что создана уменьшенная версия
        $resizedPath = str_replace('images/', 'images/resized/', $imagePath);
        Storage::disk('public')->assertExists($resizedPath);
    }

    public function test_can_attach_images_to_product(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
        ]);

        $image1 = Image::factory()->create(['product_id' => null]);
        $image2 = Image::factory()->create(['product_id' => null]);

        $this->imageService->attachImages($product, [$image1->id, $image2->id]);

        $this->assertEquals(2, $product->images()->count());
        $image1->refresh();
        $image2->refresh();
        $this->assertEquals($product->id, $image1->product_id);
        $this->assertEquals($product->id, $image2->product_id);
    }

    public function test_first_attached_image_becomes_main(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
        ]);

        $image1 = Image::factory()->create(['product_id' => null]);
        $image2 = Image::factory()->create(['product_id' => null]);

        $this->imageService->attachImages($product, [$image1->id, $image2->id]);

        $image1->refresh();
        $this->assertTrue((bool) $image1->is_main);

        $image2->refresh();
        $this->assertFalse((bool) $image2->is_main);
    }

    public function test_can_sync_images(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
        ]);

        $image1 = Image::factory()->create(['product_id' => $product->id]);
        $image2 = Image::factory()->create(['product_id' => $product->id]);
        $image3 = Image::factory()->create(['product_id' => null]);

        // Синхронизируем: оставляем image2, добавляем image3, удаляем image1
        $this->imageService->syncImages($product, [$image2->id, $image3->id]);

        $this->assertEquals(2, $product->images()->count());
        $this->assertNull(Image::find($image1->id)); // Удалено
        $this->assertNotNull(Image::find($image2->id));
        $this->assertNotNull(Image::find($image3->id));
    }

    public function test_can_set_main_image(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
        ]);

        $image1 = Image::factory()->create(['product_id' => $product->id, 'is_main' => true]);
        $image2 = Image::factory()->create(['product_id' => $product->id, 'is_main' => false]);

        $this->imageService->setMainImage($product, $image2->id);

        $image1->refresh();
        $image2->refresh();

        $this->assertFalse((bool) $image1->is_main);
        $this->assertTrue((bool) $image2->is_main);
    }

    public function test_can_delete_images(): void
    {
        $image1 = Image::factory()->create(['path' => 'images/test1.jpg']);
        $image2 = Image::factory()->create(['path' => 'images/test2.jpg']);

        // Создаем физические файлы
        Storage::disk('public')->put('images/test1.jpg', 'content');
        Storage::disk('public')->put('images/test2.jpg', 'content');

        $this->imageService->deleteImages([$image1->id, $image2->id]);

        $this->assertNull(Image::find($image1->id));
        $this->assertNull(Image::find($image2->id));
        Storage::disk('public')->assertMissing('images/test1.jpg');
        Storage::disk('public')->assertMissing('images/test2.jpg');
    }

    public function test_can_delete_all_product_images(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
        ]);

        Image::factory()->count(3)->create(['product_id' => $product->id]);

        $this->assertEquals(3, $product->images()->count());

        $this->imageService->deleteAllImages($product);

        $this->assertEquals(0, $product->images()->count());
    }

    public function test_delete_images_also_deletes_thumbnails_and_resized(): void
    {
        $image = Image::factory()->create(['path' => 'images/test.jpg']);

        // Создаем все версии файла
        Storage::disk('public')->put('images/test.jpg', 'content');
        Storage::disk('public')->put('images/thumbnails/test.jpg', 'content');
        Storage::disk('public')->put('images/resized/test.jpg', 'content');

        $this->imageService->deleteImages([$image->id]);

        // Проверяем, что все версии удалены
        Storage::disk('public')->assertMissing('images/test.jpg');
        Storage::disk('public')->assertMissing('images/thumbnails/test.jpg');
        Storage::disk('public')->assertMissing('images/resized/test.jpg');
    }

    public function test_attach_images_ignores_invalid_ids(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
        ]);

        $validImage = Image::factory()->create(['product_id' => null]);

        // Передаем валидный ID и несуществующий ID
        $this->imageService->attachImages($product, [$validImage->id, 99999]);

        // Привязано должно быть только валидное изображение
        $this->assertEquals(1, $product->images()->count());
        $validImage->refresh();
        $this->assertEquals($product->id, $validImage->product_id);
    }

    public function test_setting_main_image_does_nothing_for_invalid_image(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
        ]);

        $image = Image::factory()->create(['product_id' => $product->id, 'is_main' => true]);

        // Пытаемся установить главным несуществующее изображение
        $this->imageService->setMainImage($product, 99999);

        $image->refresh();
        // Текущее главное изображение должно остаться главным
        $this->assertTrue((bool) $image->is_main);
    }
}
