<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BackendProductControllerProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_products_index_returns_service_view_data(): void
    {
        [$user, $seller] = $this->createUserWithSeller();
        $category = Category::factory()->create();
        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'BackendIndexProduct_UNIQ',
        ]);

        $response = $this->actingAs($user)->get(route('account.store.products.index'));

        $response->assertStatus(200);
        $response->assertViewHas('products');
        $response->assertViewHas('categoryTree');
        $response->assertViewHas('sellerCategoryIds');
        $response->assertSee('BackendIndexProduct_UNIQ');
    }

    public function test_products_create_returns_root_categories(): void
    {
        [$user] = $this->createUserWithSeller();
        Category::factory()->create(['parent_id' => null, 'name' => 'RootCategory_UNIQ']);

        $response = $this->actingAs($user)->get(route('account.store.products.create'));

        $response->assertStatus(200);
        $response->assertViewHas('categories');
        $response->assertSee('RootCategory_UNIQ');
    }

    public function test_get_child_categories_returns_json(): void
    {
        [$user] = $this->createUserWithSeller();
        $root = Category::factory()->create();
        $child = Category::factory()->create([
            'parent_id' => $root->id,
            'name' => 'ChildCategory_UNIQ',
        ]);

        $response = $this->actingAs($user)->getJson(route('account.categories.children', [
            'parent_id' => $root->id,
        ]));

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $child->id,
                'name' => 'ChildCategory_UNIQ',
                'parent_id' => $root->id,
                'has_children' => false,
            ]);
    }

    private function createUserWithSeller(): array
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        return [$user, $seller];
    }
}
