<?php

namespace Tests\Feature;

use App\Models\Image as ProductImage;
use App\Models\Product;
use App\Models\Seller;
use App\Services\ProductLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProductLimitService;
    }

    public function test_inactive_product_does_not_require_images(): void
    {
        $this->assertSame([], $this->service->validateForStore(false, false));
        $this->assertSame([], $this->service->validateForUpdate(false, false));
    }

    public function test_active_store_product_requires_images(): void
    {
        $errors = $this->service->validateForStore(true, false);

        $this->assertArrayHasKey('is_active', $errors);
        $this->assertStringContainsString('Нельзя опубликовать товар без изображений', $errors['is_active']);
        $this->assertStringContainsString('Сохранить черновик', $errors['is_active']);
    }

    public function test_active_product_with_images_is_allowed(): void
    {
        $this->assertSame([], $this->service->validateForStore(true, true));
        $this->assertSame([], $this->service->validateForUpdate(true, true));
    }

    public function test_there_is_no_limit_on_number_of_active_products(): void
    {
        $seller = Seller::factory()->create();
        $products = Product::factory()->count(30)->create(['seller_id' => $seller->id, 'is_active' => true]);

        foreach ($products as $product) {
            ProductImage::factory()->create(['product_id' => $product->id]);
        }

        $this->assertSame([], $this->service->validateForStore(true, true));
        $this->assertNull($this->service->activationError($products->first()));
    }

    public function test_activation_error_requires_images(): void
    {
        $seller = Seller::factory()->create();
        $product = Product::factory()->inactive()->create(['seller_id' => $seller->id]);

        $error = $this->service->activationError($product);

        $this->assertNotNull($error);
        $this->assertStringContainsString('Нельзя опубликовать товар без изображений', $error);
        $this->assertStringContainsString('Сохранить черновик', $error);
    }
}
