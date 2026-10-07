<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature-тест: скоуп Product::visible().
 *
 * Логика видимости товара:
 *  - is_active = true  (продавец включил товар)
 *  - модерация одобрена и актуальна
 *  Тарифов (пакетов) больше нет — на видимость они не влияют.
 *
 * @covers \App\Models\Product::scopeVisible
 */
class ProductScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function createProduct(Seller $seller, array $state = []): Product
    {
        $category = Category::factory()->create();

        return Product::factory()->create(array_merge([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ], $state));
    }

    public function test_visible_scope_excludes_inactive_products(): void
    {
        $seller  = Seller::factory()->create();
        $product = $this->createProduct($seller, ['is_active' => false]);

        $found = Product::visible()->find($product->id);

        $this->assertNull($found, 'Неактивный товар не должен быть в visible()');
    }

    public function test_visible_scope_includes_active_product_of_seller_without_any_package(): void
    {
        $seller  = Seller::factory()->create();
        $product = $this->createProduct($seller, ['is_active' => true]);

        $found = Product::visible()->find($product->id);

        $this->assertNotNull($found, 'Активный одобренный товар должен быть виден');
    }

    public function test_visible_scope_excludes_product_without_current_approval(): void
    {
        $seller = Seller::factory()->create();
        $pending = $this->createProduct($seller, [
            'moderation_status' => ProductModerationStatus::Pending,
            'moderated_version' => null,
        ]);
        $outdated = $this->createProduct($seller, [
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 2,
            'moderated_version' => 1,
        ]);

        $this->assertNull(Product::visible()->find($pending->id));
        $this->assertNull(Product::visible()->find($outdated->id));
    }

    public function test_visible_scope_count_matches_expectations(): void
    {
        $sellerA = Seller::factory()->create();
        $sellerB = Seller::factory()->create();
        $category = Category::factory()->create();

        // 3 видимых товара
        Product::factory()->count(3)->create([
            'seller_id'   => $sellerA->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ]);
        // 2 видимых товара другого продавца (без пакетов — это больше не важно)
        Product::factory()->count(2)->create([
            'seller_id'   => $sellerB->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ]);
        // 1 неактивный
        Product::factory()->create([
            'seller_id'   => $sellerA->id,
            'category_id' => $category->id,
            'is_active'   => false,
        ]);

        $visibleCount = Product::visible()
            ->whereIn('seller_id', [$sellerA->id, $sellerB->id])
            ->count();

        $this->assertSame(5, $visibleCount);
    }

    // =========================================================================
    // Сортировка: VIP выше обычных
    // =========================================================================

    public function test_vip_products_appear_before_regular_products(): void
    {
        $seller   = Seller::factory()->create();
        $category = Category::factory()->create();

        $regular = Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
            'is_vip'      => false,
            'push_at'     => now()->subMinutes(5),
        ]);

        $vip = Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
            'is_vip'      => true,
            'push_at'     => now()->subMinutes(10), // более старый, но VIP
        ]);

        $ids = Product::visible()
            ->whereIn('id', [$regular->id, $vip->id])
            ->orderByDesc('is_vip')
            ->orderByDesc('push_at')
            ->pluck('id')
            ->toArray();

        $this->assertSame($vip->id, $ids[0], 'VIP товар должен идти первым');
    }
}
