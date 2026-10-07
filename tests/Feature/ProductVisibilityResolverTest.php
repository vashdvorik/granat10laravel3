<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Seller;
use App\Services\ProductVisibilityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVisibilityResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_visibility_is_true_for_approved_active_product(): void
    {
        $product = $this->product([
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 2,
            'moderated_version' => 2,
        ], true);

        $snapshot = app(ProductVisibilityResolver::class)->snapshot($product);

        $this->assertTrue($snapshot->publicVisible);
        $this->assertTrue($snapshot->buyerVisible);
        $this->assertEmpty($snapshot->buyerBlockingReasons);
    }

    public function test_buyer_visibility_is_blocked_when_product_has_no_images(): void
    {
        $product = $this->product([
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 2,
            'moderated_version' => 2,
        ], false);

        $snapshot = app(ProductVisibilityResolver::class)->snapshot($product);

        $this->assertTrue($snapshot->publicVisible);
        $this->assertFalse($snapshot->buyerVisible);
        $this->assertContains('no_images', $snapshot->buyerBlockingReasons);
    }

    public function test_public_visibility_does_not_depend_on_any_package(): void
    {
        $product = Product::factory()->create([
            'seller_id' => Seller::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 1,
            'moderated_version' => 1,
        ]);

        $snapshot = app(ProductVisibilityResolver::class)->snapshot($product);

        $this->assertTrue($snapshot->publicVisible);
        $this->assertNotContains('no_active_package', $snapshot->buyerBlockingReasons);
    }

    private function product(array $attributes, bool $withImage): Product
    {
        $seller = Seller::factory()->create();

        $product = Product::factory()->create(array_merge([
            'seller_id' => $seller->id,
            'category_id' => Category::factory()->create()->id,
        ], $attributes));

        if ($withImage) {
            Image::factory()->main()->create(['product_id' => $product->id]);
        }

        return $product;
    }
}
