<?php

namespace Tests\Feature;

use App\Application\Products\Queries\ListSellerProducts;
use App\Enums\ProductModerationStatus;
use App\Models\Category;
use App\Models\Image;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListSellerProductsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_filters_products_and_builds_seller_category_tree(): void
    {
        $seller = Seller::factory()->create();
        $root = Category::factory()->create();
        $category = Category::factory()->create(['parent_id' => $root->id]);
        $otherCategory = Category::factory()->create();
        $visibleProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ]);
        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $otherCategory->id,
        ]);

        $result = app(ListSellerProducts::class)->execute(
            $seller,
            $category->id,
            ['category' => $category->id]
        );

        $this->assertSame([$visibleProduct->id], $result['products']->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$category->id, $otherCategory->id],
            $result['sellerCategoryIds']
        );
        $this->assertTrue($result['categoryTree']->contains('id', $root->id));
        $this->assertStringContainsString(
            'category='.$category->id,
            $result['products']->url(1)
        );
    }

    public function test_query_filters_visible_products(): void
    {
        $seller = $this->sellerWithPackage();
        $visibleProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'name' => 'VisibleProduct_UNIQ',
            'is_active' => true,
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 1,
            'moderated_version' => 1,
        ]);
        Image::factory()->main()->create(['product_id' => $visibleProduct->id]);
        Product::factory()->draft()->create([
            'seller_id' => $seller->id,
            'name' => 'DraftProduct_UNIQ',
        ]);
        Product::factory()->create([
            'seller_id' => $seller->id,
            'name' => 'NoImageProduct_UNIQ',
            'is_active' => true,
        ]);

        $result = app(ListSellerProducts::class)->execute(
            $seller,
            null,
            ['product_status' => 'visible']
        );

        $this->assertSame([$visibleProduct->id], $result['products']->pluck('id')->all());
        $this->assertSame('visible', $result['currentProductStatus']);
    }

    public function test_query_filters_moderation_fixes_no_images_and_vip(): void
    {
        $seller = $this->sellerWithPackage();
        $pendingProduct = Product::factory()->pendingModeration()->create(['seller_id' => $seller->id]);
        $fixProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'is_active' => false,
        ]);
        $noImageProduct = Product::factory()->create(['seller_id' => $seller->id]);
        $vipProduct = Product::factory()->vip()->create(['seller_id' => $seller->id]);
        Image::factory()->main()->create(['product_id' => $pendingProduct->id]);
        Image::factory()->main()->create(['product_id' => $fixProduct->id]);
        Image::factory()->main()->create(['product_id' => $vipProduct->id]);

        $moderation = app(ListSellerProducts::class)->execute($seller, null, ['product_status' => 'moderation']);
        $fixes = app(ListSellerProducts::class)->execute($seller, null, ['product_status' => 'fixes']);
        $noImages = app(ListSellerProducts::class)->execute($seller, null, ['product_status' => 'no_images']);
        $vip = app(ListSellerProducts::class)->execute($seller, null, ['product_status' => 'vip']);

        $this->assertSame([$pendingProduct->id], $moderation['products']->pluck('id')->all());
        $this->assertSame([$fixProduct->id], $fixes['products']->pluck('id')->all());
        $this->assertSame([$noImageProduct->id], $noImages['products']->pluck('id')->all());
        $this->assertSame([$vipProduct->id], $vip['products']->pluck('id')->all());
    }

    public function test_query_filters_products_hidden_by_tariff(): void
    {
        $seller = Seller::factory()->create();
        $activeProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);
        Product::factory()->inactive()->create(['seller_id' => $seller->id]);

        $result = app(ListSellerProducts::class)->execute(
            $seller,
            null,
            ['product_status' => 'tariff']
        );

        $this->assertSame([$activeProduct->id], $result['products']->pluck('id')->all());
    }

    public function test_status_filter_links_are_available_in_result(): void
    {
        $seller = $this->sellerWithPackage();
        Product::factory()->draft()->create(['seller_id' => $seller->id]);

        $result = app(ListSellerProducts::class)->execute($seller);

        $this->assertContains('visible', array_column($result['productStatusFilters'], 'key'));
        $this->assertContains('hidden', array_column($result['productStatusFilters'], 'key'));
        $this->assertContains('draft', array_column($result['productStatusFilters'], 'key'));
        $this->assertSame('all', $result['currentProductStatus']);
    }

    private function sellerWithPackage(): Seller
    {
        $seller = Seller::factory()->create();
        $package = Package::factory()->withLimits(10, 2)->create();
        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);

        return $seller;
    }
}
