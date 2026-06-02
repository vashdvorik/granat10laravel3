<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use App\Services\PackageService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageServiceTest extends TestCase
{
    use RefreshDatabase;

    private PackageService $packageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->packageService = app(PackageService::class);
    }

    public function test_can_activate_package_for_seller(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'duration_days' => 30,
            'active_products_limit' => 10,
        ]);

        $sellerPackage = $this->packageService->activatePackage($seller, $package);

        $this->assertInstanceOf(SellerPackage::class, $sellerPackage);
        $this->assertEquals($seller->id, $sellerPackage->seller_id);
        $this->assertEquals($package->id, $sellerPackage->package_id);
        $this->assertTrue($sellerPackage->is_active);
        $this->assertNotNull($sellerPackage->start_date);
        $this->assertNotNull($sellerPackage->end_date);
    }

    public function test_activating_new_package_deactivates_current_package(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $package1 = Package::factory()->create(['duration_days' => 30]);
        $package2 = Package::factory()->create(['duration_days' => 60]);

        // Активируем первый пакет
        $sellerPackage1 = $this->packageService->activatePackage($seller, $package1);
        $this->assertTrue($sellerPackage1->is_active);

        // Активируем второй пакет
        $sellerPackage2 = $this->packageService->activatePackage($seller, $package2);

        // Проверяем, что первый пакет деактивирован
        $sellerPackage1->refresh();
        $this->assertFalse($sellerPackage1->is_active);
        $this->assertTrue($sellerPackage2->is_active);
    }

    public function test_can_deactivate_current_package(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create();

        $sellerPackage = $this->packageService->activatePackage($seller, $package);
        $this->assertTrue($sellerPackage->is_active);

        $this->packageService->deactivateCurrentPackage($seller);

        $sellerPackage->refresh();
        $this->assertFalse($sellerPackage->is_active);
    }

    public function test_can_extend_package(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['duration_days' => 30]);

        $sellerPackage = $this->packageService->activatePackage($seller, $package);
        $originalEndDate = $sellerPackage->end_date;

        $this->packageService->extendPackage($sellerPackage, 15);

        $sellerPackage->refresh();
        $this->assertTrue($sellerPackage->end_date->gt($originalEndDate));
        $this->assertEquals(15, $originalEndDate->diffInDays($sellerPackage->end_date));
    }

    public function test_can_activate_product_returns_true_when_within_limit(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'active_products_limit' => 5,
        ]);

        $this->packageService->activatePackage($seller, $package);

        // Создаем 3 активных продукта (меньше лимита)
        Product::factory()->count(3)->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $result = $this->packageService->canActivateProduct($seller);

        $this->assertTrue($result);
    }

    public function test_can_activate_product_returns_false_when_limit_reached(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'active_products_limit' => 3,
        ]);

        $this->packageService->activatePackage($seller, $package);

        // Создаем 3 активных продукта (достигли лимита)
        Product::factory()->count(3)->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $result = $this->packageService->canActivateProduct($seller);

        $this->assertFalse($result);
    }

    public function test_can_activate_vip_product_checks_vip_limit(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'vip_products_limit' => 2,
        ]);

        $this->packageService->activatePackage($seller, $package);

        // Создаем 1 VIP продукт
        Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
            'is_vip' => true,
        ]);

        $result = $this->packageService->canActivateVipProduct($seller);

        $this->assertTrue($result);
    }

    public function test_get_package_usage_stats_returns_correct_data(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'active_products_limit' => 10,
            'vip_products_limit' => 2,
        ]);

        $this->packageService->activatePackage($seller, $package);

        // Создаем 5 обычных активных продуктов
        Product::factory()->count(5)->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
            'is_vip' => false,
        ]);

        // Создаем 1 VIP продукт
        Product::factory()->create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'is_active' => true,
            'is_vip' => true,
        ]);

        $stats = $this->packageService->getPackageUsageStats($seller);

        $this->assertEquals(6, $stats['active_products_count']);
        $this->assertEquals(10, $stats['active_products_limit']);
        $this->assertEquals(1, $stats['vip_products_count']);
        $this->assertEquals(2, $stats['vip_products_limit']);
        $this->assertEquals(4, $stats['active_products_remaining']);
        $this->assertEquals(1, $stats['vip_products_remaining']);
    }

    public function test_returns_false_when_no_active_package(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $result = $this->packageService->canActivateProduct($seller);

        $this->assertFalse($result);
    }

    public function test_package_expiration_is_calculated_correctly(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['duration_days' => 45]);

        Carbon::setTestNow(Carbon::create(2026, 1, 1));

        $sellerPackage = $this->packageService->activatePackage($seller, $package);

        $expectedEndDate = Carbon::create(2026, 1, 1)->addDays(45);
        $this->assertEquals($expectedEndDate->toDateString(), $sellerPackage->end_date->toDateString());

        Carbon::setTestNow();
    }
}
