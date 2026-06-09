<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\Settlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_product_move_clears_old_and_new_category_and_seller_caches(): void
    {
        $oldCategory = Category::factory()->create();
        $newCategory = Category::factory()->create();
        $oldSeller = Seller::factory()->create(['alias' => 'old-shop']);
        $newSeller = Seller::factory()->create(['alias' => 'new-shop']);
        $product = Product::factory()->create([
            'category_id' => $oldCategory->id,
            'seller_id' => $oldSeller->id,
        ]);

        Cache::put('category_products_count:'.$oldCategory->id, 1);
        Cache::put('category_products_count:'.$newCategory->id, 0);
        Cache::put('seller_by_alias:old-shop', $oldSeller);
        Cache::put('seller_by_alias:new-shop', $newSeller);
        Cache::put('sellers_list', collect([$oldSeller, $newSeller]));

        $product->update([
            'category_id' => $newCategory->id,
            'seller_id' => $newSeller->id,
        ]);

        $this->assertFalse(Cache::has('category_products_count:'.$oldCategory->id));
        $this->assertFalse(Cache::has('category_products_count:'.$newCategory->id));
        $this->assertFalse(Cache::has('seller_by_alias:old-shop'));
        $this->assertFalse(Cache::has('seller_by_alias:new-shop'));
        $this->assertFalse(Cache::has('sellers_list'));
    }

    public function test_seller_alias_update_clears_old_and_new_alias_caches(): void
    {
        $seller = Seller::factory()->create(['alias' => 'old-alias']);

        Cache::put('seller_by_alias:old-alias', $seller);
        Cache::put('seller_by_alias:new-alias', $seller);

        $seller->update(['alias' => 'new-alias']);

        $this->assertFalse(Cache::has('seller_by_alias:old-alias'));
        $this->assertFalse(Cache::has('seller_by_alias:new-alias'));
    }

    public function test_seller_package_update_clears_visibility_dependent_caches(): void
    {
        $category = Category::factory()->create();
        $seller = Seller::factory()->create();
        $package = Package::factory()->create();
        $sellerPackage = SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);

        Cache::put('category_products_count:'.$category->id, 1);
        Cache::put('product_price_range', ['min' => 10, 'max' => 20]);
        Cache::put('menu_categories', 'stale-menu');
        Cache::put('sellers_list', collect([$seller]));

        $sellerPackage->update(['is_active' => false]);

        $this->assertFalse(Cache::has('category_products_count:'.$category->id));
        $this->assertFalse(Cache::has('product_price_range'));
        $this->assertFalse(Cache::has('sellers_list'));
        $this->assertNotSame('stale-menu', Cache::get('menu_categories'));
    }

    public function test_seller_settlement_change_clears_regional_visibility_caches(): void
    {
        $oldSettlement = Settlement::factory()->create();
        $newSettlement = Settlement::factory()->create();
        $category = Category::factory()->create();
        $seller = Seller::factory()->for($oldSettlement)->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'seller_id' => $seller->id,
            'is_active' => true,
        ]);

        foreach ([$oldSettlement, $newSettlement] as $settlement) {
            Cache::put('product_price_range:settlement:'.$settlement->id, ['min' => 10, 'max' => 20]);
            Cache::put('menu_categories:settlement:'.$settlement->id, 'stale-menu');
            Cache::put('category_products_count:'.$category->id.':settlement:'.$settlement->id, 1);
        }

        $seller->update(['settlement_id' => $newSettlement->id]);

        foreach ([$oldSettlement, $newSettlement] as $settlement) {
            $this->assertFalse(Cache::has('product_price_range:settlement:'.$settlement->id));
            $this->assertFalse(Cache::has('menu_categories:settlement:'.$settlement->id));
            $this->assertFalse(Cache::has(
                'category_products_count:'.$category->id.':settlement:'.$settlement->id
            ));
        }
    }
}
