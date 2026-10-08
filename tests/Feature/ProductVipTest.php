<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Services\ProductVipService;
use App\Services\VipPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ProductVipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-10-04 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // =========================================================================
    // Цены
    // =========================================================================

    public function test_default_price_is_one_ruble_per_day(): void
    {
        $pricing = app(VipPricing::class);

        $this->assertSame('1.00', $pricing->pricePerDay());
        $this->assertSame('1.00', $pricing->price(1));
        $this->assertSame('7.00', $pricing->price(7));
        $this->assertSame('30.00', $pricing->price(30));
        $this->assertSame('365.00', $pricing->price(365));
        $this->assertSame([1, 7, 30, 365], array_column($pricing->options(), 'days'));
    }

    public function test_admin_can_change_price_per_day_and_it_persists(): void
    {
        $pricing = app(VipPricing::class);
        $pricing->setPricePerDay('2,50');

        Cache::flush();

        $this->assertSame('2.50', app(VipPricing::class)->pricePerDay());
        $this->assertSame('17.50', app(VipPricing::class)->price(7));
        $this->assertDatabaseHas('site_settings', ['key' => 'vip_price_per_day', 'value' => '250']);
    }

    public function test_invalid_price_per_day_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(VipPricing::class)->setPricePerDay('-5');
    }

    // =========================================================================
    // Покупка VIP (сервис)
    // =========================================================================

    public function test_purchase_charges_balance_and_sets_vip_until(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);

        $updated = app(ProductVipService::class)->purchase($user, $product, 7);

        $this->assertTrue($updated->is_vip);
        $this->assertTrue($updated->vip_until->equalTo(now()->addDays(7)));
        $this->assertEquals(93.00, (float) $user->fresh()->balance);
        $this->assertTrue($updated->isVipActive());
    }

    public function test_purchase_prices_for_each_duration(): void
    {
        foreach ([1, 7, 30, 365] as $days) {
            [$user, $product] = $this->sellerProduct(balance: 1000);

            app(ProductVipService::class)->purchase($user, $product, $days);

            $this->assertEquals(1000 - $days, (float) $user->fresh()->balance, "Срок {$days} дн.");
        }
    }

    public function test_purchase_uses_price_set_by_administrator(): void
    {
        app(VipPricing::class)->setPricePerDay('2.50');
        [$user, $product] = $this->sellerProduct(balance: 100);

        app(ProductVipService::class)->purchase($user, $product, 7);

        $this->assertEquals(82.50, (float) $user->fresh()->balance);
    }

    public function test_purchase_extends_active_vip_from_its_end_date(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);
        $product->forceFill(['is_vip' => true, 'vip_until' => now()->addDays(3)])->save();

        $updated = app(ProductVipService::class)->purchase($user, $product, 7);

        $this->assertTrue($updated->vip_until->equalTo(now()->addDays(10)));
        $this->assertEquals(93.00, (float) $user->fresh()->balance);
    }

    public function test_purchase_restarts_from_now_when_previous_vip_expired(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);
        $product->forceFill(['is_vip' => true, 'vip_until' => now()->subDay()])->save();

        $updated = app(ProductVipService::class)->purchase($user, $product, 1);

        $this->assertTrue($updated->vip_until->equalTo(now()->addDay()));
    }

    public function test_purchase_fails_without_enough_money_and_changes_nothing(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 5);

        try {
            app(ProductVipService::class)->purchase($user, $product, 7);
            $this->fail('Ожидалось исключение о нехватке средств');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Недостаточно средств', $exception->getMessage());
        }

        $this->assertEquals(5.00, (float) $user->fresh()->balance);
        $this->assertFalse($product->fresh()->is_vip);
        $this->assertNull($product->fresh()->vip_until);
    }

    public function test_purchase_rejects_unknown_duration(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);

        $this->expectException(RuntimeException::class);

        app(ProductVipService::class)->purchase($user, $product, 5);
    }

    public function test_purchase_rejects_foreign_product(): void
    {
        [$user] = $this->sellerProduct(balance: 100);
        [, $foreignProduct] = $this->sellerProduct(balance: 0);

        $this->expectException(RuntimeException::class);

        app(ProductVipService::class)->purchase($user, $foreignProduct, 1);
    }

    public function test_purchase_rejects_unpublished_product(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);
        $product->forceFill(['is_active' => false])->save();

        try {
            app(ProductVipService::class)->purchase($user, $product, 1);
            $this->fail('Ожидалось исключение');
        } catch (RuntimeException) {
            $this->assertEquals(100.00, (float) $user->fresh()->balance);
        }
    }

    public function test_purchase_rejects_permanent_admin_vip(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);
        $product->forceFill(['is_vip' => true, 'vip_until' => null])->save();

        $this->expectException(RuntimeException::class);

        app(ProductVipService::class)->purchase($user, $product, 1);
    }

    public function test_purchase_is_written_to_activity_log(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);

        app(ProductVipService::class)->purchase($user, $product, 7);

        $activity = Activity::query()
            ->where('log_name', 'products')
            ->where('properties->action', 'vip_purchase')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($user->id, $activity->causer_id);
        $this->assertSame($product->id, $activity->subject_id);
        $this->assertSame('7.00', $activity->properties['price']);
    }

    // =========================================================================
    // Истечение VIP
    // =========================================================================

    public function test_expire_due_removes_only_expired_vip(): void
    {
        $seller = Seller::factory()->create();
        $expired = Product::factory()->create(['seller_id' => $seller->id, 'is_vip' => true, 'vip_until' => now()->subMinute()]);
        $active = Product::factory()->create(['seller_id' => $seller->id, 'is_vip' => true, 'vip_until' => now()->addDay()]);
        $permanent = Product::factory()->create(['seller_id' => $seller->id, 'is_vip' => true, 'vip_until' => null]);
        $regular = Product::factory()->create(['seller_id' => $seller->id, 'is_vip' => false]);

        $count = app(ProductVipService::class)->expireDue();

        $this->assertSame(1, $count);
        $this->assertFalse($expired->fresh()->is_vip);
        $this->assertNull($expired->fresh()->vip_until);
        $this->assertTrue($active->fresh()->is_vip);
        $this->assertTrue($permanent->fresh()->is_vip);
        $this->assertFalse($regular->fresh()->is_vip);
    }

    public function test_expire_command_runs_and_is_scheduled(): void
    {
        $product = Product::factory()->create(['is_vip' => true, 'vip_until' => now()->subHour()]);

        $this->artisan('products:expire-vip')
            ->expectsOutputToContain('Снят VIP с товаров: 1')
            ->assertSuccessful();

        $this->assertFalse($product->fresh()->is_vip);

        $scheduled = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->contains(fn ($event) => str_contains((string) $event->command, 'products:expire-vip'));

        $this->assertTrue($scheduled, 'Команда снятия VIP должна быть в расписании');
    }

    public function test_is_vip_active_respects_expiry(): void
    {
        $this->assertFalse((new Product(['is_vip' => false]))->isVipActive());

        $permanent = new Product;
        $permanent->is_vip = true;
        $this->assertTrue($permanent->isVipActive());

        $future = new Product;
        $future->is_vip = true;
        $future->vip_until = now()->addHour();
        $this->assertTrue($future->isVipActive());

        $past = new Product;
        $past->is_vip = true;
        $past->vip_until = now()->subHour();
        $this->assertFalse($past->isVipActive());
    }

    public function test_turning_vip_off_clears_its_end_date(): void
    {
        $product = Product::factory()->create(['is_vip' => true, 'vip_until' => now()->addDays(5)]);

        $product->update(['is_vip' => false]);

        $this->assertNull($product->fresh()->vip_until);
    }

    // =========================================================================
    // Кабинет продавца (HTTP)
    // =========================================================================

    public function test_seller_buys_vip_from_product_page(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 50);

        $response = $this->actingAs($user)
            ->from(route('account.store.products.show', $product))
            ->post(route('account.store.products.vip', $product), ['days' => 7]);

        $response->assertRedirect(route('account.store.products.show', $product));
        $response->assertSessionHas('success');
        $this->assertEquals(43.00, (float) $user->fresh()->balance);
        $this->assertTrue($product->fresh()->is_vip);
    }

    public function test_vip_purchase_with_low_balance_shows_error(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 3);

        $response = $this->actingAs($user)
            ->from(route('account.store.products.show', $product))
            ->post(route('account.store.products.vip', $product), ['days' => 30]);

        $response->assertRedirect(route('account.store.products.show', $product));
        $response->assertSessionHas('error');
        $this->assertEquals(3.00, (float) $user->fresh()->balance);
        $this->assertFalse($product->fresh()->is_vip);
    }

    public function test_vip_purchase_validates_duration(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 50);

        $this->actingAs($user)
            ->post(route('account.store.products.vip', $product), ['days' => 3])
            ->assertSessionHasErrors('days');

        $this->actingAs($user)
            ->post(route('account.store.products.vip', $product), [])
            ->assertSessionHasErrors('days');

        $this->assertEquals(50.00, (float) $user->fresh()->balance);
    }

    public function test_seller_cannot_buy_vip_for_foreign_product(): void
    {
        [$user] = $this->sellerProduct(balance: 50);
        [, $foreignProduct] = $this->sellerProduct(balance: 0);

        $this->actingAs($user)
            ->post(route('account.store.products.vip', $foreignProduct), ['days' => 1])
            ->assertForbidden();

        $this->assertEquals(50.00, (float) $user->fresh()->balance);
        $this->assertFalse($foreignProduct->fresh()->is_vip);
    }

    public function test_guest_cannot_buy_vip(): void
    {
        $product = Product::factory()->create();

        $this->post(route('account.store.products.vip', $product), ['days' => 1])
            ->assertRedirect();

        $this->assertFalse($product->fresh()->is_vip);
    }

    public function test_product_page_shows_vip_options_with_prices_and_balance(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 120);

        $this->actingAs($user)
            ->get(route('account.store.products.show', $product))
            ->assertOk()
            ->assertSee('Включить VIP')
            ->assertSee('1 день')
            ->assertSee('7 дней')
            ->assertSee('Месяц (30 дней)')
            ->assertSee('Год (365 дней)')
            ->assertSee('365,00 ₽')
            ->assertSee('120,00 ₽');
    }

    public function test_product_page_shows_active_vip_with_end_date_and_offers_extension(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 120);
        $product->forceFill(['is_vip' => true, 'vip_until' => now()->addDays(2)])->save();

        $this->actingAs($user)
            ->get(route('account.store.products.show', $product))
            ->assertOk()
            ->assertSee('Продлить VIP')
            ->assertSee(now()->addDays(2)->format('d.m.Y H:i'));
    }

    public function test_seller_cannot_make_product_vip_through_product_form(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 50);

        $this->actingAs($user)->put(route('account.store.products.update', $product), [
            'name' => $product->name,
            'price' => 100,
            'category_id' => $product->category_id,
            'is_active' => 0,
            'is_vip' => 1,
            'vip_until' => now()->addYear()->toDateTimeString(),
        ]);

        $this->assertFalse($product->fresh()->is_vip);
        $this->assertNull($product->fresh()->vip_until);
    }

    public function test_editing_product_keeps_purchased_vip(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 50);
        $product->forceFill(['is_vip' => true, 'vip_until' => now()->addDays(4)])->save();

        $this->actingAs($user)->put(route('account.store.products.update', $product), [
            'name' => $product->name,
            'price' => 100,
            'category_id' => $product->category_id,
            'is_active' => 0,
        ]);

        $this->assertTrue($product->fresh()->is_vip);
        $this->assertTrue($product->fresh()->vip_until->equalTo(now()->addDays(4)));
    }

    // =========================================================================
    // Пакетов больше нет, баланс остался
    // =========================================================================

    public function test_package_system_is_gone_but_balance_stays(): void
    {
        $this->assertFalse(Schema::hasTable('packages'));
        $this->assertFalse(Schema::hasTable('seller_packages'));
        $this->assertFalse(Schema::hasColumn('sellers', 'auto_renew'));
        $this->assertTrue(Schema::hasColumn('users', 'balance'));
        $this->assertTrue(Schema::hasColumn('products', 'vip_until'));

        $this->assertFalse(Route::has('account.packages.index'));
        $this->assertFalse(Route::has('account.packages.purchase'));
        $this->assertFalse(Route::has('account.store.toggle-auto-renew'));

        foreach (['App\Models\Package', 'App\Models\SellerPackage', 'App\Services\PackageService'] as $class) {
            $this->assertFalse(class_exists($class), "{$class} должен быть удалён");
        }
    }

    /**
     * @return array{0: User, 1: Product}
     */
    private function sellerProduct(float|int $balance): array
    {
        $user = User::factory()->create(['balance' => $balance]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        return [$user, $product];
    }
}
