<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use App\Services\PackageCompatibilityService;
use App\Services\PackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PackageServiceTest extends TestCase
{
    use RefreshDatabase;

    private PackageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PackageService(app(PackageCompatibilityService::class));
    }

    public function test_purchase_creates_active_package_deactivates_previous_and_charges_user(): void
    {
        $user = User::factory()->create(['balance' => 1000]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $oldPackage = Package::factory()->create();
        $newPackage = Package::factory()->create([
            'price' => 250,
            'duration_days' => 30,
            'active_products_limit' => 10,
            'vip_products_limit' => 3,
        ]);

        $previousSellerPackage = SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $oldPackage->id,
            'is_active' => true,
        ]);

        $sellerPackage = $this->service->purchase($user, $seller, $newPackage);

        $this->assertFalse((bool) $previousSellerPackage->fresh()->is_active);
        $this->assertTrue((bool) $sellerPackage->is_active);
        $this->assertSame($newPackage->id, $sellerPackage->package_id);
        $this->assertNotNull($sellerPackage->end_date);
        $this->assertEquals(750, $user->fresh()->balance);
    }

    public function test_purchase_rolls_back_when_balance_is_not_enough(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['price' => 250]);

        try {
            $this->service->purchase($user, $seller, $package);
            $this->fail('RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        } finally {
            $this->assertSame(0, $seller->sellerPackages()->count());
            $this->assertEquals(100, $user->fresh()->balance);
        }
    }

    public function test_purchase_blocks_package_when_active_products_exceed_limit(): void
    {
        $user = User::factory()->create(['balance' => 1000]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->withLimits(1, 5)->create(['price' => 0]);

        Product::factory()->count(2)->create([
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);

        try {
            $this->service->purchase($user, $seller, $package);
            $this->fail('RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
    }

    public function test_purchase_blocks_package_when_vip_products_exceed_limit(): void
    {
        $user = User::factory()->create(['balance' => 1000]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->withLimits(10, 0)->create(['price' => 0]);

        Product::factory()->activeVip()->create([
            'seller_id' => $seller->id,
        ]);

        try {
            $this->service->purchase($user, $seller, $package);
            $this->fail('RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
    }

    public function test_renew_uses_user_balance_and_extends_package(): void
    {
        $user = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'price' => 200,
            'duration_days' => 30,
        ]);
        $sellerPackage = SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $renewedPackage = $this->service->renew($sellerPackage);

        $this->assertTrue((bool) $renewedPackage->is_active);
        $this->assertTrue($renewedPackage->end_date->isFuture());
        $this->assertEquals(300, $user->fresh()->balance);
    }

    public function test_deactivate_expired_disables_order_system_when_package_allowed_orders(): void
    {
        $seller = Seller::factory()->create(['can_order' => true]);
        $package = Package::factory()->create(['has_order_system' => true]);
        $sellerPackage = SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
        ]);

        $this->service->deactivateExpired($sellerPackage);

        $this->assertFalse((bool) $sellerPackage->fresh()->is_active);
        $this->assertFalse($seller->fresh()->can_order);
    }
}
