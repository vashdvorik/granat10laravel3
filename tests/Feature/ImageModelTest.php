<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Image;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature-тест: цикл жизни изображений товара.
 * Проверяет автоматическую инвалидацию кешей при удалении изображений.
 *
 * @covers \App\Models\Image
 */
class ImageModelTest extends TestCase
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

    private function createProductWithSeller(): array
    {
        $package  = Package::factory()->withLimits(50, 10)->create();
        $seller   = Seller::factory()->create();
        $category = Category::factory()->create();

        SellerPackage::factory()->create([
            'seller_id'  => $seller->id,
            'package_id' => $package->id,
            'is_active'  => true,
        ]);

        $product = Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ]);

        return [$product, $seller, $category];
    }

    // =========================================================================
    // Базовые тесты модели
    // =========================================================================

    public function test_image_belongs_to_product(): void
    {
        [$product] = $this->createProductWithSeller();
        $image = Image::factory()->create(['product_id' => $product->id]);

        $this->assertInstanceOf(Product::class, $image->product);
        $this->assertSame($product->id, $image->product->id);
    }

    public function test_product_has_many_images(): void
    {
        [$product] = $this->createProductWithSeller();

        Image::factory()->count(3)->create(['product_id' => $product->id]);

        $this->assertCount(3, $product->fresh()->images);
    }

    public function test_image_fillable_fields(): void
    {
        $image = new Image([
            'product_id' => 1,
            'path'       => 'products/test.jpg',
            'is_main'    => true,
        ]);

        $this->assertSame(1, $image->product_id);
        $this->assertSame('products/test.jpg', $image->path);
        $this->assertTrue($image->is_main);
    }

    public function test_image_can_be_marked_as_main(): void
    {
        [$product] = $this->createProductWithSeller();

        $mainImage  = Image::factory()->main()->create(['product_id' => $product->id]);
        $otherImage = Image::factory()->create(['product_id' => $product->id, 'is_main' => false]);

        $this->assertTrue((bool) $mainImage->is_main);
        $this->assertFalse((bool) $otherImage->is_main);
    }

    // =========================================================================
    // Тест: при удалении последнего изображения активного товара сбрасываются кеши
    // =========================================================================

    public function test_deleting_last_image_of_active_product_clears_price_range_cache(): void
    {
        [$product, $seller, $category] = $this->createProductWithSeller();

        $image = Image::factory()->create(['product_id' => $product->id]);

        // Заполняем кеш
        Cache::put('product_price_range', ['min' => 100, 'max' => 9999], 3600);
        Cache::put('sellers_list', collect([]), 3600);

        // Удаляем последнее изображение активного товара
        $image->delete();

        $this->assertFalse(
            Cache::has('product_price_range'),
            'Кеш диапазона цен должен быть сброшен когда у активного товара не осталось изображений'
        );
    }

    public function test_deleting_image_from_inactive_product_does_not_clear_cache(): void
    {
        [$product] = $this->createProductWithSeller();
        $product->update(['is_active' => false]);

        $image = Image::factory()->create(['product_id' => $product->id]);

        Cache::put('product_price_range', ['min' => 100, 'max' => 9999], 3600);

        // Неактивный товар — кеш не должен трогаться
        $image->delete();

        // Кеш остаётся нетронутым — товар и так не виден
        $this->assertTrue(
            Cache::has('product_price_range'),
            'Кеш не должен сбрасываться при удалении изображения неактивного товара'
        );
    }

    public function test_deleting_non_last_image_does_not_clear_cache(): void
    {
        [$product] = $this->createProductWithSeller();

        $image1 = Image::factory()->create(['product_id' => $product->id]);
        $image2 = Image::factory()->create(['product_id' => $product->id]); // второе изображение

        Cache::put('product_price_range', ['min' => 100, 'max' => 9999], 3600);

        // Удаляем одно из двух изображений — товар остаётся с изображением
        $image1->delete();

        $this->assertTrue(
            Cache::has('product_price_range'),
            'Кеш не должен сбрасываться при удалении не последнего изображения'
        );
    }

    // =========================================================================
    // Каскадное удаление
    // =========================================================================

    public function test_images_deleted_when_product_deleted(): void
    {
        [$product] = $this->createProductWithSeller();
        Image::factory()->count(2)->create(['product_id' => $product->id]);

        $imageIds = $product->images()->pluck('id');

        $product->delete();

        foreach ($imageIds as $id) {
            $this->assertDatabaseMissing('images', ['id' => $id]);
        }
    }
}
