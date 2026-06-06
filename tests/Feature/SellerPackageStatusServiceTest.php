<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use App\Services\SellerPackageStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerPackageStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private SellerPackageStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SellerPackageStatusService();
    }

    public function test_status_without_package_reports_hidden_products(): void
    {
        $seller = Seller::factory()->create();
        Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);

        $status = $this->service->getStatus($seller);

        $this->assertFalse($status['has_package']);
        $this->assertStringContainsString('1 шт.', $status['description']);
        $this->assertSame('danger', $status['alert_type']);
    }

    public function test_permanent_package_returns_success_status(): void
    {
        $seller = Seller::factory()->create();
        $package = Package::factory()->free()->create(['name' => 'Базовый']);

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'end_date' => null,
        ]);

        $status = $this->service->getStatus($seller->fresh());

        $this->assertTrue($status['has_package']);
        $this->assertSame('Базовый', $status['package_name']);
        $this->assertSame('success', $status['alert_type']);
    }

    public function test_expiring_package_uses_user_balance_for_auto_renew(): void
    {
        $user = User::factory()->create(['balance' => 1000]);
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'auto_renew' => true,
        ]);
        $package = Package::factory()->create([
            'name' => 'Стандарт',
            'price' => 500,
            'duration_days' => 30,
        ]);

        SellerPackage::factory()->expiresInDays(3)->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
        ]);

        $status = $this->service->getStatus($seller->fresh());

        $this->assertTrue($status['can_auto_renew']);
        $this->assertEquals(1000, $status['balance']);
        $this->assertStringContainsString('Ваш баланс: 1000', $status['description']);
        $this->assertSame('warning', $status['alert_type']);
    }

    public function test_expiring_package_reports_insufficient_user_balance(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'auto_renew' => true,
        ]);
        $package = Package::factory()->create([
            'price' => 500,
            'duration_days' => 30,
        ]);

        SellerPackage::factory()->expiresInDays(3)->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
        ]);

        $status = $this->service->getStatus($seller->fresh());

        $this->assertFalse($status['can_auto_renew']);
        $this->assertStringContainsString('баланс: 100', $status['description']);
        $this->assertSame('danger', $status['alert_type']);
    }
}
