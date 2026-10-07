<?php

namespace Tests\Feature;

use App\Application\Products\Queries\ListSellerProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Services\BackendProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BackendProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private BackendProductService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->service = new BackendProductService;
    }

    public function test_generate_unique_alias_adds_suffix_when_alias_exists(): void
    {
        Product::factory()->create(['alias' => 'test-product']);

        $alias = $this->service->generateUniqueAlias('Test Product');

        $this->assertSame('test-product-1', $alias);
    }

    public function test_get_selected_path_returns_ancestors_and_category(): void
    {
        $root = Category::factory()->create(['name' => 'Root']);
        $child = Category::factory()->create(['name' => 'Child', 'parent_id' => $root->id]);

        $path = $this->service->getSelectedPath($child->id);

        $this->assertSame([$root->id, $child->id], $path);
    }

    public function test_get_index_data_filters_products_by_category(): void
    {
        $seller = $this->createSeller();
        $targetCategory = Category::factory()->create();
        $otherCategory = Category::factory()->create();
        $targetProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $targetCategory->id,
            'is_active' => true,
        ]);
        $otherProduct = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $otherCategory->id,
            'is_active' => true,
        ]);

        $data = app(ListSellerProducts::class)->execute(
            $seller,
            $targetCategory->id,
            ['category' => $targetCategory->id]
        );

        $this->assertTrue($data['products']->contains($targetProduct));
        $this->assertFalse($data['products']->contains($otherProduct));
        $this->assertContains($targetCategory->id, $data['sellerCategoryIds']);
    }

    public function test_get_child_categories_returns_active_children_with_has_children_flag(): void
    {
        $root = Category::factory()->create(['number' => 1]);
        $child = Category::factory()->create(['parent_id' => $root->id, 'number' => 1]);
        Category::factory()->create(['parent_id' => $root->id, 'is_active' => false, 'number' => 2]);
        Category::factory()->create(['parent_id' => $child->id, 'number' => 1]);

        $categories = $this->service->getChildCategories($root->id);

        $this->assertCount(1, $categories);
        $this->assertSame($child->id, $categories->first()->id);
        $this->assertTrue((bool) $categories->first()->has_children);
    }

    private function createSeller(): Seller
    {
        $seller = Seller::factory()->create();


        return $seller;
    }
}
