<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProductPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_is_allowed_all_product_abilities(): void
    {
        [$owner, $product] = $this->createOwnedProduct();

        foreach ($this->abilities() as $ability) {
            $this->assertTrue(
                Gate::forUser($owner)->allows($ability, $product),
                "Owner should be allowed to {$ability} product."
            );
        }
    }

    public function test_other_user_is_denied_all_product_abilities(): void
    {
        [, $product] = $this->createOwnedProduct();
        $otherUser = User::factory()->create();

        foreach ($this->abilities() as $ability) {
            $this->assertFalse(
                Gate::forUser($otherUser)->allows($ability, $product),
                "Other user should not be allowed to {$ability} product."
            );
        }
    }

    public function test_backend_product_routes_forbid_non_owner(): void
    {
        [, $product] = $this->createOwnedProduct();
        $image = Image::factory()->create(['product_id' => $product->id]);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        $this->get(route('account.store.products.show', $product))->assertForbidden();
        $this->get(route('account.store.products.edit', $product))->assertForbidden();
        $this->delete(route('account.store.products.destroy', $product))->assertForbidden();
        $this->patch(route('account.store.products.activate', $product))->assertForbidden();
        $this->patch(route('account.store.products.deactivate', $product))->assertForbidden();
        $this->post(route('account.store.products.push-up', $product))->assertForbidden();
        $this->delete(route('account.store.products.images.destroy', $image))->assertForbidden();
        $this->patch(route('account.store.products.images.set-main', $image))->assertForbidden();
    }

    private function createOwnedProduct(): array
    {
        $owner = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $owner->id]);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
        ]);

        return [$owner, $product];
    }

    private function abilities(): array
    {
        return [
            'view',
            'update',
            'delete',
            'activate',
            'deactivate',
            'manageImages',
            'pushUp',
        ];
    }
}
