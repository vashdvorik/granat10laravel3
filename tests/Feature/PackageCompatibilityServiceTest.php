<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use App\Services\PackageCompatibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageCompatibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_is_available_when_balance_and_limits_fit(): void
    {
        $user = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'price' => 100,
            'active_products_limit' => 10,
            'vip_products_limit' => 2,
        ]);

        Product::factory()->count(2)->create([
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);

        $compatibility = app(PackageCompatibilityService::class)->make($user, $seller, $package);

        $this->assertSame('available', $compatibility->status);
        $this->assertTrue($compatibility->canPurchaseNow);
        $this->assertTrue($compatibility->hasEnoughBalance);
        $this->assertSame([], $compatibility->reasons);
    }

    public function test_package_requires_preparation_when_active_product_limit_is_exceeded(): void
    {
        $user = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create([
            'price' => 100,
            'active_products_limit' => 1,
            'vip_products_limit' => 10,
        ]);

        Product::factory()->count(3)->create([
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);

        $compatibility = app(PackageCompatibilityService::class)->make($user, $seller, $package);

        $this->assertSame('needs_preparation', $compatibility->status);
        $this->assertFalse($compatibility->canPurchaseNow);
        $this->assertSame(2, $compatibility->requiredActiveDeactivations);
        $this->assertFalse($compatibility->activeProductsFit);
        $this->assertStringContainsString('Нужно снять с публикации 2', $compatibility->reasons[0]);
    }

    public function test_package_blocks_purchase_when_balance_is_not_enough(): void
    {
        $user = User::factory()->create(['balance' => 50]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['price' => 100]);

        $compatibility = app(PackageCompatibilityService::class)->make($user, $seller, $package);

        $this->assertSame('insufficient_balance', $compatibility->status);
        $this->assertFalse($compatibility->canPurchaseNow);
        $this->assertFalse($compatibility->hasEnoughBalance);
        $this->assertSame('Недостаточно средств на балансе.', $compatibility->reasons[0]);
    }

    public function test_package_warns_when_features_will_be_hidden(): void
    {
        $user = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'link' => 'https://example.com',
            'telegram' => '@shop',
            'can_order' => true,
        ]);
        $package = Package::factory()->create([
            'price' => 100,
            'active_products_limit' => 10,
            'vip_products_limit' => 2,
            'has_website_link' => false,
            'has_social_links' => false,
            'has_order_system' => false,
        ]);

        $compatibility = app(PackageCompatibilityService::class)->make($user, $seller, $package);

        $this->assertSame('feature_downgrade', $compatibility->status);
        $this->assertTrue($compatibility->canPurchaseNow);
        $this->assertTrue($compatibility->willDisableWebsite);
        $this->assertTrue($compatibility->willDisableSocialLinks);
        $this->assertTrue($compatibility->willDisableOrders);
        $this->assertCount(3, $compatibility->warnings);
    }

    public function test_current_package_is_marked_as_current(): void
    {
        $user = User::factory()->create(['balance' => 500]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['price' => 0]);
        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);

        $compatibility = app(PackageCompatibilityService::class)->make($user, $seller, $package);

        $this->assertSame('current', $compatibility->status);
        $this->assertTrue($compatibility->isCurrent);
        $this->assertFalse($compatibility->canPurchaseNow);
    }
}
