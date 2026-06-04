<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeactivateExpiredPackagesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_auto_renews_expired_package_from_user_balance(): void
    {
        $user = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'auto_renew' => true,
        ]);
        $package = Package::factory()->create([
            'price' => 200,
            'duration_days' => 30,
        ]);
        $sellerPackage = SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'end_date' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $this->artisan('packages:deactivate-expired', ['--auto-renew' => true])
            ->assertExitCode(0);

        $this->assertTrue((bool) $sellerPackage->fresh()->is_active);
        $this->assertTrue($sellerPackage->fresh()->end_date->isFuture());
        $this->assertEquals(300, $user->fresh()->balance);
    }

    public function test_command_deactivates_expired_package_without_auto_renew(): void
    {
        $user = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'can_order' => true,
        ]);
        $package = Package::factory()->create([
            'has_order_system' => true,
            'price' => 200,
        ]);
        $sellerPackage = SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'end_date' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $this->artisan('packages:deactivate-expired')
            ->assertExitCode(0);

        $this->assertFalse((bool) $sellerPackage->fresh()->is_active);
        $this->assertFalse($seller->fresh()->can_order);
        $this->assertEquals(500, $user->fresh()->balance);
    }
}
