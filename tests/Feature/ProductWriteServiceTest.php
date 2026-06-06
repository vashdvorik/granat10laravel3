<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Seller;
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
        ]);

        $this->assertSame($seller->id, $product->seller_id);
        $this->assertSame('test-product-1', $product->alias);
        $this->assertFalse((bool) $product->is_active);
        $this->assertTrue((bool) $product->is_vip);
        $this->assertNotNull($product->sale_started_at);
        $this->assertNotNull($product->push_at);
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
        ]);

        $this->assertSame('Updated Product', $updated->name);
        $this->assertTrue((bool) $updated->is_vip);
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
}
