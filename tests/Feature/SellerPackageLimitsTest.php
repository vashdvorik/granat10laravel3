<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature-тест: бизнес-логика тарифных лимитов продавца (работа с БД).
 *
 * @covers \App\Models\Seller::canActivateProduct
 * @covers \App\Models\Seller::canActivateVipProduct
 * @covers \App\Models\Seller::checkProductLimits
 * @covers \App\Models\Seller::getActiveProductsCount
 * @covers \App\Models\Seller::getVipProductsCount
 */
class SellerPackageLimitsTest extends TestCase
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

    private function createSellerWithPackage(int $activeLimit = 5, int $vipLimit = 2): Seller
    {
        $package = Package::factory()->withLimits($activeLimit, $vipLimit)->create();
        $seller  = Seller::factory()->create();

        SellerPackage::factory()->create([
            'seller_id'  => $seller->id,
            'package_id' => $package->id,
            'is_active'  => true,
            'end_date'   => null,
        ]);

        // Перезагружаем seller чтобы сбросить in-memory кеш activePackage
        return $seller->fresh();
    }

    private function addActiveProduct(Seller $seller, bool $isVip = false): Product
    {
        return Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => Category::factory()->create()->id,
            'is_active'   => true,
            'is_vip'      => $isVip,
        ]);
    }

    // =========================================================================
    // getActiveProductsCount / getVipProductsCount
    // =========================================================================

    public function test_get_active_products_count_counts_only_active(): void
    {
        $seller = $this->createSellerWithPackage();
        $category = Category::factory()->create();

        Product::factory()->count(3)->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_active' => true]);
        Product::factory()->count(2)->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_active' => false]);

        $this->assertSame(3, $seller->getActiveProductsCount());
    }

    public function test_get_vip_products_count_counts_only_active_vip(): void
    {
        $seller = $this->createSellerWithPackage();
        $category = Category::factory()->create();

        Product::factory()->count(2)->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_active' => true, 'is_vip' => true]);
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_active' => true, 'is_vip' => false]);
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_active' => false, 'is_vip' => true]); // неактивный

        $this->assertSame(2, $seller->getVipProductsCount());
    }

    // =========================================================================
    // canActivateProduct
    // =========================================================================

    public function test_can_activate_product_without_existing_products(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 5);

        $result = $seller->canActivateProduct();

        $this->assertTrue($result['allowed']);
        $this->assertSame(0, $result['current']);
        $this->assertSame(5, $result['limit']);
    }

    public function test_can_activate_product_below_limit(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 5);

        $this->addActiveProduct($seller);
        $this->addActiveProduct($seller);

        $result = $seller->canActivateProduct();

        $this->assertTrue($result['allowed']);
        $this->assertSame(2, $result['current']);
    }

    public function test_cannot_activate_product_at_limit(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 3);

        $this->addActiveProduct($seller);
        $this->addActiveProduct($seller);
        $this->addActiveProduct($seller);

        $result = $seller->canActivateProduct();

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('3/3', $result['message']);
    }

    public function test_can_activate_product_when_no_active_package(): void
    {
        $seller = Seller::factory()->create(); // без пакета

        $result = $seller->canActivateProduct();

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('нет активного тарифного пакета', $result['message']);
        $this->assertSame(0, $result['current']);
        $this->assertSame(0, $result['limit']);
    }

    public function test_can_activate_product_excludes_self_on_edit(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 3);

        $p1 = $this->addActiveProduct($seller);
        $p2 = $this->addActiveProduct($seller);
        $p3 = $this->addActiveProduct($seller); // 3/3 лимит исчерпан

        // При редактировании p3 — исключаем его из подсчёта → current = 2 < 3
        $result = $seller->canActivateProduct(excludeProductId: $p3->id);

        $this->assertTrue($result['allowed'], 'При редактировании своего товара должно быть allowed=true');
        $this->assertSame(2, $result['current']);
    }

    // =========================================================================
    // canActivateVipProduct
    // =========================================================================

    public function test_can_activate_vip_product_below_vip_limit(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 10, vipLimit: 3);

        $this->addActiveProduct($seller, isVip: true); // 1 VIP

        $result = $seller->canActivateVipProduct();

        $this->assertTrue($result['allowed']);
        $this->assertSame(1, $result['current']);
        $this->assertSame(3, $result['limit']);
    }

    public function test_cannot_activate_vip_product_at_vip_limit(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 10, vipLimit: 2);

        $this->addActiveProduct($seller, isVip: true);
        $this->addActiveProduct($seller, isVip: true); // 2/2 лимит VIP

        $result = $seller->canActivateVipProduct();

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('2/2', $result['message']);
    }

    public function test_inactive_vip_product_does_not_count_to_vip_limit(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 10, vipLimit: 2);
        $category = Category::factory()->create();

        // Неактивный VIP — не считается
        Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => false,
            'is_vip'      => true,
        ]);

        $result = $seller->canActivateVipProduct();

        $this->assertTrue($result['allowed']);
        $this->assertSame(0, $result['current']);
    }

    // =========================================================================
    // checkProductLimits
    // =========================================================================

    public function test_check_limits_inactive_product_always_allowed(): void
    {
        $seller = Seller::factory()->create(); // без пакета

        $result = $seller->checkProductLimits(isActive: false, isVip: false);

        $this->assertTrue($result['allowed']);
        $this->assertEmpty($result['errors']);
    }

    public function test_check_limits_active_product_within_limits(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 5, vipLimit: 2);

        $result = $seller->checkProductLimits(isActive: true, isVip: false);

        $this->assertTrue($result['allowed']);
    }

    public function test_check_limits_active_vip_within_both_limits(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 5, vipLimit: 2);

        $result = $seller->checkProductLimits(isActive: true, isVip: true);

        $this->assertTrue($result['allowed']);
        $this->assertEmpty($result['errors']);
    }

    public function test_check_limits_returns_multiple_errors_when_both_exceeded(): void
    {
        $seller = $this->createSellerWithPackage(activeLimit: 1, vipLimit: 1);
        $this->addActiveProduct($seller, isVip: true); // заполнили оба лимита

        $result = $seller->checkProductLimits(isActive: true, isVip: true);

        $this->assertFalse($result['allowed']);
        $this->assertCount(2, $result['errors']);
    }

    // =========================================================================
    // Срок действия пакета
    // =========================================================================

    public function test_expired_package_blocks_activation(): void
    {
        $package = Package::factory()->withLimits(10, 5)->create();
        $seller  = Seller::factory()->create();

        SellerPackage::factory()->create([
            'seller_id'  => $seller->id,
            'package_id' => $package->id,
            'is_active'  => true,
            'end_date'   => now()->subDay()->toDateString(), // просрочен вчера
        ]);

        $result = $seller->fresh()->canActivateProduct();

        $this->assertFalse($result['allowed']);
        $this->assertSame(0, $result['limit']);
    }

    public function test_package_expiring_today_is_still_active(): void
    {
        $package = Package::factory()->withLimits(10, 5)->create();
        $seller  = Seller::factory()->create();

        SellerPackage::factory()->create([
            'seller_id'  => $seller->id,
            'package_id' => $package->id,
            'is_active'  => true,
            'end_date'   => now()->toDateString(), // истекает сегодня
        ]);

        $result = $seller->fresh()->canActivateProduct();

        $this->assertTrue($result['allowed'], 'Пакет, истекающий сегодня, должен ещё быть активным');
    }
}
