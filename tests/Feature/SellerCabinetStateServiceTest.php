<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Image;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use App\Services\SellerCabinetStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerCabinetStateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_marks_store_hidden_without_active_package(): void
    {
        $seller = Seller::factory()->create();
        Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);

        $state = app(SellerCabinetStateService::class)->make($seller);

        $this->assertSame('hidden_no_package', $state->status);
        $this->assertFalse($state->hasActivePackage);
        $this->assertSame(1, $state->activeProductsCount);
        $this->assertSame(0, $state->visibleProductsCount);
        $this->assertSame('no_active_package', $state->primaryBlocker()['code']);
        $this->assertSame('choose_package', $state->primaryAction);
    }

    public function test_state_counts_visible_products_separately_from_active_products(): void
    {
        $seller = $this->sellerWithPackage();
        $visible = Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 1,
            'moderated_version' => 1,
        ]);
        Image::factory()->main()->create(['product_id' => $visible->id]);
        Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Pending,
            'moderated_version' => null,
        ]);

        $state = app(SellerCabinetStateService::class)->make($seller);

        $this->assertSame(2, $state->activeProductsCount);
        $this->assertSame(1, $state->visibleProductsCount);
        $this->assertSame('needs_attention', $state->status);
        $this->assertContains('products_need_moderation', array_column($state->blockers, 'code'));
    }

    public function test_state_exposes_tariff_limited_profile_fields(): void
    {
        $seller = $this->sellerWithPackage([
            'has_website_link' => false,
            'has_social_links' => false,
            'has_order_system' => false,
        ], [
            'link' => 'https://example.com',
            'telegram' => '@shop',
            'can_order' => true,
        ]);

        $state = app(SellerCabinetStateService::class)->make($seller);
        $codes = array_column($state->blockers, 'code');

        $this->assertContains('website_hidden_by_package', $codes);
        $this->assertContains('social_links_hidden_by_package', $codes);
        $this->assertContains('orders_disabled_by_package', $codes);
        $this->assertFalse($state->canShowWebsite);
        $this->assertFalse($state->canShowSocialLinks);
        $this->assertFalse($state->canAcceptOrders);
    }

    public function test_state_exposes_expiring_package_renewal_details(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'auto_renew' => true,
        ]);
        $package = Package::factory()->create([
            'price' => 250,
            'duration_days' => 30,
        ]);
        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => now()->addDays(3)->toDateString(),
        ]);

        $state = app(SellerCabinetStateService::class)->make($seller);

        $this->assertSame(250, (int) $state->packageRenewalPrice);
        $this->assertSame(100, (int) $state->balance);
        $this->assertSame(3, $state->packageDaysLeft);
        $this->assertTrue($state->autoRenewEnabled);
        $this->assertFalse($state->autoRenewPossible);
        $this->assertContains('auto_renew_insufficient_balance', array_column($state->blockers, 'code'));
    }

    private function sellerWithPackage(array $packageState = [], array $sellerState = []): Seller
    {
        $user = User::factory()->create(['balance' => 1000]);
        $seller = Seller::factory()->create(array_merge([
            'user_id' => $user->id,
        ], $sellerState));
        $package = Package::factory()->create(array_merge([
            'active_products_limit' => 10,
            'vip_products_limit' => 2,
            'has_order_system' => true,
        ], $packageState));
        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);

        return $seller;
    }
}
