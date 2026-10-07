<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Image;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Services\SellerCabinetStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerCabinetStateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_without_store_asks_to_create_it(): void
    {
        $state = app(SellerCabinetStateService::class)->make(null);

        $this->assertSame('no_store', $state->status);
        $this->assertSame('create_store', $state->primaryAction);
    }

    public function test_state_for_store_without_visible_products_is_empty(): void
    {
        $seller = Seller::factory()->create();
        Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Pending,
            'moderated_version' => null,
        ]);

        $state = app(SellerCabinetStateService::class)->make($seller);

        $this->assertSame('empty', $state->status);
        $this->assertSame(1, $state->activeProductsCount);
        $this->assertSame(0, $state->visibleProductsCount);
        $codes = array_column($state->blockers, 'code');
        $this->assertContains('no_visible_products', $codes);
        $this->assertNotContains('no_active_package', $codes);
    }

    public function test_state_counts_visible_products_separately_from_active_products(): void
    {
        $seller = Seller::factory()->create();
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

    public function test_state_exposes_balance_and_has_no_package_fields(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $seller = Seller::factory()->create([
            'user_id' => $user->id,
            'link' => 'https://example.com',
            'telegram' => '@shop',
            'can_order' => true,
        ]);

        $state = app(SellerCabinetStateService::class)->make($seller);
        $codes = array_column($state->blockers, 'code');

        $this->assertSame(100, (int) $state->balance);
        $this->assertTrue($state->canAcceptOrders);
        $this->assertNotContains('website_hidden_by_package', $codes);
        $this->assertNotContains('social_links_hidden_by_package', $codes);
        $this->assertNotContains('orders_disabled_by_package', $codes);
        $this->assertFalse(property_exists($state, 'packageName'));
        $this->assertFalse(property_exists($state, 'autoRenewEnabled'));
    }

    public function test_state_reports_when_seller_disabled_orders(): void
    {
        $seller = Seller::factory()->create(['can_order' => false]);

        $state = app(SellerCabinetStateService::class)->make($seller);

        $this->assertFalse($state->canAcceptOrders);
        $this->assertContains('orders_disabled_by_seller', array_column($state->blockers, 'code'));
    }
}
