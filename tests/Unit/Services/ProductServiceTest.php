<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Services\ImageService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;

    private ImageService $imageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->imageService = $this->createMock(ImageService::class);
        $this->productService = new ProductService($this->imageService);
    }

    public function test_can_create_product(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        // Мокируем ImageService
        $this->imageService
            ->expects($this->never())
            ->method('attachImages');

        $data = [
            'name' => 'Test Product',
            'description' => 'Test description for the product that is long enough',
            'price' => 1000.00,
            'category_id' => 1,
        ];

        $product = $this->productService->createProduct($seller, $data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals(1000.00, $product->price);
        $this->assertEquals($seller->id, $product->seller_id);
        $this->assertTrue($product->is_active);
    }

    public function test_cannot_create_product_when_limit_reached(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage(__('errors.product_limit_reached'));

        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        // Мокируем метод canActivateProduct чтобы вернуть false
        $sellerMock = $this->getMockBuilder(Seller::class)
            ->onlyMethods(['canActivateProduct'])
            ->getMock();

        $sellerMock->method('canActivateProduct')
            ->willReturn(false);

        $sellerMock->id = $seller->id;
        $sellerMock->user_id = $seller->user_id;

        $data = [
            'name' => 'Test Product',
            'description' => 'Test description for the product that is long enough',
            'price' => 1000.00,
            'category_id' => 1,
        ];

        $this->productService->createProduct($sellerMock, $data);
    }

    public function test_can_update_product(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
        ]);

        $data = [
            'name' => 'Updated Product',
            'price' => 2000.00,
        ];

        $updatedProduct = $this->productService->updateProduct($product, $data);

        $this->assertEquals('Updated Product', $updatedProduct->name);
        $this->assertEquals(2000.00, $updatedProduct->price);
    }

    public function test_can_delete_product(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
        ]);

        // Мокируем ImageService
        $this->imageService
            ->expects($this->once())
            ->method('deleteAllImages')
            ->with($product);

        $result = $this->productService->deleteProduct($product);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_can_get_visible_products(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        // Создаем несколько продуктов
        Product::factory()->count(3)->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $products = $this->productService->getVisibleProducts();

        $this->assertGreaterThanOrEqual(3, $products->total());
    }

    public function test_can_filter_products_by_category(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'category_id' => 1,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'category_id' => 2,
            'is_active' => true,
        ]);

        $products = $this->productService->getVisibleProducts(['category_id' => 1]);

        $this->assertEquals(1, $products->total());
        $this->assertEquals(1, $products->first()->category_id);
    }
}
