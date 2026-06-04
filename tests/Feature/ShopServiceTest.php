<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Seller;
use App\Models\User;
use App\Services\ShopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ShopServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShopService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->service = new ShopService();
    }

    public function test_create_shop_creates_seller_and_assigns_base_package(): void
    {
        $basePackage = Package::factory()->free()->create(['id' => ShopService::BASE_PACKAGE_ID]);
        $user = User::factory()->create();

        $seller = $this->service->createShop($user, [
            'name' => 'Test Shop',
            'phone' => '+1',
            'town' => 'City',
            'sellerplace' => 'Market',
            'address' => 'Street',
            'description' => 'Description',
        ]);

        $this->assertSame($user->id, $seller->user_id);
        $this->assertSame('Test Shop', $seller->name);
        $this->assertSame('test-shop', $seller->alias);
        $this->assertDatabaseHas('seller_packages', [
            'seller_id' => $seller->id,
            'package_id' => $basePackage->id,
            'is_active' => true,
        ]);
    }

    public function test_create_shop_generates_unique_alias_when_alias_exists(): void
    {
        Package::factory()->free()->create(['id' => ShopService::BASE_PACKAGE_ID]);
        Seller::factory()->create(['alias' => 'test-shop']);

        $seller = $this->service->createShop(User::factory()->create(), [
            'name' => 'Test Shop',
        ]);

        $this->assertStringStartsWith('test-shop-', $seller->alias);
    }

    public function test_create_shop_rolls_back_when_base_package_is_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Базовый тариф не найден');

        try {
            $this->service->createShop(User::factory()->create(), [
                'name' => 'No Package Shop',
            ]);
        } finally {
            $this->assertDatabaseMissing('sellers', [
                'name' => 'No Package Shop',
            ]);
        }
    }

    public function test_create_shop_stores_logo_as_webp(): void
    {
        Package::factory()->free()->create(['id' => ShopService::BASE_PACKAGE_ID]);

        $seller = $this->service->createShop(
            User::factory()->create(),
            ['name' => 'Logo Shop'],
            UploadedFile::fake()->image('logo.jpg', 800, 800)
        );

        $this->assertNotNull($seller->photo);
        $this->assertStringEndsWith('.webp', $seller->photo);
        Storage::disk('public')->assertExists($seller->photo);
    }
}
