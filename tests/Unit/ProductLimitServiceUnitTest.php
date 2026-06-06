<?php

namespace Tests\Unit;

use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Services\ProductLimitService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Unit-тест: бизнес-логика лимитов тарифного пакета продавца.
 * Данные кладём напрямую во фреймворк-мок, БД не трогаем.
 *
 * @covers \App\Services\ProductLimitService
 */
class ProductLimitServiceUnitTest extends TestCase
{
    private ProductLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::spy();
        $this->service = new ProductLimitService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // canActivateProduct
    // =========================================================================

    public function test_cannot_activate_product_without_package(): void
    {
        $seller = $this->sellerMockWithNoPackage();

        $result = $this->service->canActivateProduct($seller);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('нет активного тарифного пакета', $result['message']);
        $this->assertSame(0, $result['limit']);
    }

    public function test_can_activate_product_below_limit(): void
    {
        // Лимит 5, уже активно 3 → можно
        $seller = $this->sellerMockWithPackage(
            activeLimit: 5,
            vipLimit: 2,
            currentActive: 3,
            currentVip: 0
        );

        $result = $this->service->canActivateProduct($seller);

        $this->assertTrue($result['allowed']);
        $this->assertSame(3, $result['current']);
        $this->assertSame(5, $result['limit']);
    }

    public function test_cannot_activate_product_at_limit(): void
    {
        // Лимит 5, уже активно 5 → нельзя
        $seller = $this->sellerMockWithPackage(
            activeLimit: 5,
            vipLimit: 2,
            currentActive: 5,
            currentVip: 0
        );

        $result = $this->service->canActivateProduct($seller);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('Достигнут лимит', $result['message']);
        $this->assertStringContainsString('5/5', $result['message']);
    }

    // =========================================================================
    // canActivateVipProduct
    // =========================================================================

    public function test_cannot_activate_vip_without_package(): void
    {
        $seller = $this->sellerMockWithNoPackage();

        $result = $this->service->canActivateVipProduct($seller);

        $this->assertFalse($result['allowed']);
    }

    public function test_can_activate_vip_below_limit(): void
    {
        $seller = $this->sellerMockWithPackage(
            activeLimit: 10,
            vipLimit: 3,
            currentActive: 2,
            currentVip: 1
        );

        $result = $this->service->canActivateVipProduct($seller);

        $this->assertTrue($result['allowed']);
        $this->assertSame(1, $result['current']);
        $this->assertSame(3, $result['limit']);
    }

    public function test_cannot_activate_vip_at_vip_limit(): void
    {
        $seller = $this->sellerMockWithPackage(
            activeLimit: 10,
            vipLimit: 3,
            currentActive: 2,
            currentVip: 3  // лимит VIP исчерпан
        );

        $result = $this->service->canActivateVipProduct($seller);

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('3/3', $result['message']);
    }

    // =========================================================================
    // checkProductLimits
    // =========================================================================

    public function test_inactive_product_is_always_allowed(): void
    {
        // Нет пакета — но товар неактивный, поэтому ограничений нет
        $seller = $this->sellerMockWithNoPackage();

        $result = $this->service->checkProductLimits($seller, isActive: false, isVip: false);

        $this->assertTrue($result['allowed']);
        $this->assertEmpty($result['errors']);
    }

    public function test_active_non_vip_product_allowed_within_limit(): void
    {
        $seller = $this->sellerMockWithPackage(
            activeLimit: 10,
            vipLimit: 3,
            currentActive: 4,
            currentVip: 0
        );

        $result = $this->service->checkProductLimits($seller, isActive: true, isVip: false);

        $this->assertTrue($result['allowed']);
        $this->assertEmpty($result['errors']);
    }

    public function test_active_vip_product_blocked_by_active_limit(): void
    {
        $seller = $this->sellerMockWithPackage(
            activeLimit: 5,
            vipLimit: 3,
            currentActive: 5,  // активный лимит исчерпан
            currentVip: 0
        );

        $result = $this->service->checkProductLimits($seller, isActive: true, isVip: true);

        $this->assertFalse($result['allowed']);
        // Должна быть ошибка по активному лимиту
        $this->assertNotEmpty($result['errors']);
    }

    public function test_active_vip_product_blocked_by_vip_limit(): void
    {
        $seller = $this->sellerMockWithPackage(
            activeLimit: 10,
            vipLimit: 3,
            currentActive: 2,
            currentVip: 3  // VIP лимит исчерпан
        );

        $result = $this->service->checkProductLimits($seller, isActive: true, isVip: true);

        $this->assertFalse($result['allowed']);
        $this->assertCount(1, $result['errors']);
    }

    public function test_active_vip_product_blocked_by_both_limits(): void
    {
        $seller = $this->sellerMockWithPackage(
            activeLimit: 5,
            vipLimit: 3,
            currentActive: 5,  // оба лимита исчерпаны
            currentVip: 3
        );

        $result = $this->service->checkProductLimits($seller, isActive: true, isVip: true);

        $this->assertFalse($result['allowed']);
        $this->assertCount(2, $result['errors']);
    }

    // =========================================================================
    // Helpers — создаём mock Seller с нужным состоянием
    // =========================================================================

    private function sellerMockWithNoPackage(): Seller|MockInterface
    {
        /** @var MockInterface|Seller $seller */
        $seller = Mockery::mock(Seller::class)->makePartial();
        $seller->shouldReceive('activePackage')->andReturn(null);
        return $seller;
    }

    private function sellerMockWithPackage(
        int $activeLimit,
        int $vipLimit,
        int $currentActive,
        int $currentVip
    ): Seller|MockInterface {
        $package = new Package([
            'name'                  => 'Test',
            'active_products_limit' => $activeLimit,
            'vip_products_limit'    => $vipLimit,
        ]);

        $sellerPackage = new SellerPackage();
        $sellerPackage->setRelation('package', $package);

        /** @var MockInterface|Seller $seller */
        $seller = Mockery::mock(Seller::class)->makePartial();
        $seller->shouldReceive('activePackage')->andReturn($sellerPackage);

        // Каждый вызов products() создаёт свежий query-mock.
        // Mock отслеживает, цеплялся ли where('is_vip') в цепочке,
        // и возвращает соответствующий count.
        $makeQuery = function () use ($currentActive, $currentVip) {
            $vipChained = false;
            $q = Mockery::mock();
            $q->shouldReceive('where')->with('is_vip', true)
                ->andReturnUsing(function () use ($q, &$vipChained) {
                    $vipChained = true;
                    return $q;
                });
            $q->shouldReceive('where')->andReturnSelf();
            $q->shouldReceive('count')
                ->andReturnUsing(function () use (&$vipChained, $currentActive, $currentVip) {
                    return $vipChained ? $currentVip : $currentActive;
                });
            return $q;
        };

        $seller->shouldReceive('products')->andReturnUsing($makeQuery);

        return $seller;
    }
}
