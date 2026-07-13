<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Image;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Services\ProductCabinetStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCabinetStateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_marks_approved_active_product_as_visible_to_buyers(): void
    {
        $seller = $this->sellerWithPackage();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 2,
            'moderated_version' => 2,
        ]);
        Image::factory()->main()->create(['product_id' => $product->id]);

        $state = app(ProductCabinetStateService::class)->make($product);

        $this->assertSame('visible', $state->status);
        $this->assertTrue($state->visibleToBuyers);
        $this->assertNull($state->hiddenReason);
        $this->assertSame('push_up', $state->primaryAction);
    }

    public function test_state_marks_product_hidden_when_package_is_missing(): void
    {
        $seller = Seller::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 1,
            'moderated_version' => 1,
        ]);
        Image::factory()->main()->create(['product_id' => $product->id]);

        $state = app(ProductCabinetStateService::class)->make($product);

        $this->assertSame('hidden_no_package', $state->status);
        $this->assertFalse($state->visibleToBuyers);
        $this->assertSame('no_active_package', $state->hiddenReason);
        $this->assertSame('choose_package', $state->primaryAction);
    }

    public function test_state_marks_changed_product_as_requiring_repeat_moderation(): void
    {
        $seller = $this->sellerWithPackage();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 3,
            'moderated_version' => 2,
        ]);
        Image::factory()->main()->create(['product_id' => $product->id]);

        $state = app(ProductCabinetStateService::class)->make($product);

        $this->assertSame('hidden_moderation_outdated', $state->status);
        $this->assertSame('moderation_outdated', $state->hiddenReason);
        $this->assertSame('submit', $state->primaryAction);
    }

    public function test_state_marks_approved_product_without_images_as_hidden(): void
    {
        $seller = $this->sellerWithPackage();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 1,
            'moderated_version' => 1,
        ]);

        $state = app(ProductCabinetStateService::class)->make($product);

        $this->assertSame('hidden_no_images', $state->status);
        $this->assertSame('no_images', $state->hiddenReason);
        $this->assertSame('add_images', $state->primaryAction);
    }

    private function sellerWithPackage(): Seller
    {
        $seller = Seller::factory()->create(['can_order' => true]);
        $package = Package::factory()->withLimits(10, 2)->create(['has_order_system' => true]);
        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);

        return $seller;
    }
}
