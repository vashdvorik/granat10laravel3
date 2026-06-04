<?php

namespace Tests\Feature;

use App\Models\Image as ProductImage;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
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

        $this->service = new ProductLimitService();
    }

    public function test_inactive_product_does_not_require_package_or_images(): void
    {
        $seller = Seller::factory()->create();

        $errors = $this->service->validateForStore($seller, false, false, false);

        $this->assertSame([], $errors);
    }

    public function test_active_store_product_requires_images(): void
    {
        $seller = $this->createSellerWithPackage();

        $errors = $this->service->validateForStore($seller, true, false, false);

        $this->assertArrayHasKey('is_active', $errors);
        $this->assertStringContainsString('Нельзя активировать товар без изображений', $errors['is_active']);
    }

    public function test_active_store_product_respects_active_limit(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 1);
        Product::factory()->create(['seller_id' => $seller->id, 'is_active' => true]);

        $errors = $this->service->validateForStore($seller, true, false, true);

        $this->assertArrayHasKey('is_active', $errors);
        $this->assertStringContainsString('1/1', $errors['is_active']);
    }

    public function test_vip_store_product_respects_vip_limit(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 5, vipLimit: 1);
        Product::factory()->activeVip()->create(['seller_id' => $seller->id]);

        $errors = $this->service->validateForStore($seller, true, true, true);

        $this->assertArrayHasKey('is_vip', $errors);
        $this->assertStringContainsString('1/1', $errors['is_vip']);
    }

    public function test_update_excludes_current_product_from_limit(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 1);
        $product = Product::factory()->create(['seller_id' => $seller->id, 'is_active' => true]);
        ProductImage::factory()->create(['product_id' => $product->id]);

        $errors = $this->service->validateForUpdate($product, true, false, true);

        $this->assertSame([], $errors);
    }

    public function test_activation_error_checks_images_before_limits(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 1);
        $product = Product::factory()->inactive()->create(['seller_id' => $seller->id]);

        $error = $this->service->activationError($product);

        $this->assertNotNull($error);
        $this->assertStringContainsString('Нельзя активировать товар без изображений', $error);
    }

    private function createSellerWithPackage(int $activeLimit = 5, int $vipLimit = 2): Seller
    {
        $package = Package::factory()->withLimits($activeLimit, $vipLimit)->create();
        $seller = Seller::factory()->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        return $seller->fresh();
    }
}
