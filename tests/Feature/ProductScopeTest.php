<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature-тест: скоупы Product::visible() и Product::fromActiveSellerPackage().
 *
 * Логика видимости товара:
 *  - is_active = true  (продавец включил товар)
 *  - у продавца есть активный SellerPackage (is_active=true, end_date не просрочена)
 *
 * @covers \App\Models\Product::scopeVisible
 * @covers \App\Models\Product::scopeFromActiveSellerPackage
 */
class ProductScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // =========================================================================
    // Хелперы
    // =========================================================================

    private function createSellerWithPackage(array $packageState = []): Seller
    {
        $package = Package::factory()->withLimits(50, 10)->create();
        $seller  = Seller::factory()->create();

        SellerPackage::factory()->create(array_merge([
            'seller_id'  => $seller->id,
            'package_id' => $package->id,
            'is_active'  => true,
            'end_date'   => null,
        ], $packageState));

        return $seller;
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

    // =========================================================================
    // Тесты: fromActiveSellerPackage
    // =========================================================================

    public function test_product_visible_when_seller_has_active_permanent_package(): void
    {
        $seller  = $this->createSellerWithPackage(['end_date' => null]);
        $product = $this->createProduct($seller);

        $found = Product::fromActiveSellerPackage()->find($product->id);

        $this->assertNotNull($found, 'Товар с активным бессрочным пакетом должен быть виден');
    }

    public function test_product_visible_when_package_expires_in_future(): void
    {
        $seller  = $this->createSellerWithPackage(['end_date' => now()->addDays(10)->toDateString()]);
        $product = $this->createProduct($seller);

        $found = Product::fromActiveSellerPackage()->find($product->id);

        $this->assertNotNull($found);
    }

    public function test_product_hidden_when_seller_package_expired(): void
    {
        $seller = $this->createSellerWithPackage([
            'end_date'  => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);
        $product = $this->createProduct($seller);

        $found = Product::fromActiveSellerPackage()->find($product->id);

        $this->assertNull($found, 'Товар продавца с просроченным пакетом должен быть скрыт');
    }

    public function test_product_hidden_when_seller_package_inactive(): void
    {
        $seller = $this->createSellerWithPackage([
            'is_active' => false,
            'end_date'  => now()->addDays(30)->toDateString(),
        ]);
        $product = $this->createProduct($seller);

        $found = Product::fromActiveSellerPackage()->find($product->id);

        $this->assertNull($found, 'Товар продавца с деактивированным пакетом должен быть скрыт');
    }

    public function test_product_hidden_when_seller_has_no_package(): void
    {
        $seller  = Seller::factory()->create(); // нет пакета
        $product = $this->createProduct($seller);

        $found = Product::fromActiveSellerPackage()->find($product->id);

        $this->assertNull($found, 'Товар продавца без пакета должен быть скрыт');
    }

    // =========================================================================
    // Тесты: visible (is_active + активный пакет)
    // =========================================================================

    public function test_visible_scope_excludes_inactive_products(): void
    {
        $seller  = $this->createSellerWithPackage();
        $product = $this->createProduct($seller, ['is_active' => false]);

        $found = Product::visible()->find($product->id);

        $this->assertNull($found, 'Неактивный товар не должен быть в visible()');
    }

    public function test_visible_scope_includes_active_product_with_active_package(): void
    {
        $seller  = $this->createSellerWithPackage();
        $product = $this->createProduct($seller, ['is_active' => true]);

        $found = Product::visible()->find($product->id);

        $this->assertNotNull($found, 'Активный товар с активным пакетом должен быть виден');
    }

    public function test_visible_scope_excludes_active_product_with_expired_package(): void
    {
        $seller = $this->createSellerWithPackage([
            'end_date' => now()->subDay()->toDateString(),
        ]);
        $product = $this->createProduct($seller, ['is_active' => true]);

        $found = Product::visible()->find($product->id);

        $this->assertNull($found, 'Активный товар с истёкшим пакетом не должен быть виден');
    }

    public function test_visible_scope_excludes_product_without_current_approval(): void
    {
        $seller = $this->createSellerWithPackage();
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
        $sellerWithPackage    = $this->createSellerWithPackage();
        $sellerWithoutPackage = Seller::factory()->create();
        $category             = Category::factory()->create();

        // 3 видимых товара
        Product::factory()->count(3)->create([
            'seller_id'   => $sellerWithPackage->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ]);
        // 2 невидимых (нет пакета)
        Product::factory()->count(2)->create([
            'seller_id'   => $sellerWithoutPackage->id,
            'category_id' => $category->id,
            'is_active'   => true,
        ]);
        // 1 неактивный (есть пакет, но сам скрыт)
        Product::factory()->create([
            'seller_id'   => $sellerWithPackage->id,
            'category_id' => $category->id,
            'is_active'   => false,
        ]);

        $visibleCount = Product::visible()
            ->whereIn('seller_id', [$sellerWithPackage->id, $sellerWithoutPackage->id])
            ->count();

        $this->assertSame(3, $visibleCount);
    }

    // =========================================================================
    // Сортировка: VIP выше обычных
    // =========================================================================

    public function test_vip_products_appear_before_regular_products(): void
    {
        $seller   = $this->createSellerWithPackage();
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
