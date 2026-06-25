<?php

namespace Tests\Feature;

use App\Application\Products\Queries\ListSellerProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
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
}
