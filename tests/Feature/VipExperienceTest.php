<?php

namespace Tests\Feature;

use App\Data\VipState;
use App\Enums\BalanceTransactionType;
use App\Enums\ProductModerationStatus;
use App\Models\BalanceTransaction;
use App\Models\Image;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Models\VipPurchase;
use App\Services\ProductVipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

/** VIP-размещение целиком: расчёт состояния, покупка, возврат, журнал, страницы кабинета. */
class VipExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-10-09 12:00:00'));
        config(['vip.default_price_per_day' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @return array{0: User, 1: Product} */
    private function sellerProduct(float|int $balance = 100, array $product = []): array
    {
        $user = User::factory()->create(['balance' => $balance]);
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['seller_id' => $seller->id] + $product);

        return [$user, $product];
    }

    private function service(): ProductVipService
    {
        return app(ProductVipService::class);
    }

    private function balance(User $user): float
    {
        return (float) $user->fresh()->getRawOriginal('balance');
    }

    // ------------------------------------------------------------------ состояние

    public function test_state_of_plain_product_lists_options_with_end_dates_and_affordability(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 10);

        $state = $this->service()->state($product, $user);

        $this->assertSame(VipState::INACTIVE, $state->status);
        $this->assertTrue($state->eligible);
        $this->assertTrue($state->canBuy());
        $this->assertSame(1000, $state->balanceCents);
        $this->assertCount(4, $state->options);

        $week = collect($state->options)->firstWhere('days', 7);
        $this->assertSame(700, $week['price_cents']);
        $this->assertTrue($week['affordable']);
        $this->assertSame('2026-10-16 12:00:00', $week['new_until']->toDateTimeString());

        $year = collect($state->options)->firstWhere('days', 365);
        $this->assertFalse($year['affordable']);
        $this->assertSame(36500 - 1000, $year['shortfall_cents']);
        $this->assertSame(0, $state->cheapestShortfallCents());
    }

    public function test_state_explains_why_vip_is_unavailable(): void
    {
        [$user, $inactive] = $this->sellerProduct(product: ['is_active' => false]);
        $pending = Product::factory()->create(['seller_id' => $inactive->seller_id, 'moderation_status' => ProductModerationStatus::Pending]);

        $messages = collect($this->service()->state($inactive, $user)->blockers)->pluck('message')->implode(' ');
        $this->assertStringContainsString('снят с публикации', $messages);
        $this->assertFalse($this->service()->state($inactive, $user)->canBuy());

        $messages = collect($this->service()->state($pending, $user)->blockers)->pluck('message')->implode(' ');
        $this->assertStringContainsString('не прошёл проверку', $messages);
    }

    public function test_state_of_active_vip_extends_from_current_end_and_warns_before_expiry(): void
    {
        [$user, $product] = $this->sellerProduct();
        $product->forceFill(['is_vip' => true, 'vip_until' => now()->addDays(2)])->save();

        $state = $this->service()->state($product->fresh(), $user);

        $this->assertSame(VipState::ACTIVE, $state->status);
        $this->assertSame(2, $state->daysLeft);
        $this->assertTrue($state->expiresSoon);
        $this->assertSame('2026-10-18 12:00:00', collect($state->options)->firstWhere('days', 7)['new_until']->toDateTimeString());
    }

    public function test_state_of_permanent_vip_offers_nothing_to_buy(): void
    {
        [$user, $product] = $this->sellerProduct();
        $this->service()->setByAdmin($product, true, null);

        $state = $this->service()->state($product->fresh(), $user);

        $this->assertSame(VipState::PERMANENT, $state->status);
        $this->assertFalse($state->canBuy());
        $this->assertSame([], $state->options);
        $this->assertFalse($state->canCancel);
        $this->assertNotNull($state->cancelBlockedReason);
    }

    public function test_product_without_photo_gets_warning_but_can_buy(): void
    {
        [$user, $product] = $this->sellerProduct();

        $state = $this->service()->state($product, $user);

        $this->assertTrue($state->eligible);
        $this->assertStringContainsString('нет фото', implode(' ', $state->warnings));

        Image::factory()->create(['product_id' => $product->id]);
        $this->assertSame([], $this->service()->state($product->fresh(), $user)->warnings);
    }

    // ------------------------------------------------------------------ покупка и журнал

    public function test_purchase_records_history_and_balance_journal(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);

        $this->service()->purchase($user, $product, 7);

        $purchase = VipPurchase::query()->firstOrFail();
        $this->assertSame(700, $purchase->price_cents);
        $this->assertSame(7, $purchase->days);
        $this->assertSame('2026-10-16 12:00:00', $purchase->ends_at->toDateTimeString());
        $this->assertEquals(93, $this->balance($user));

        $entry = BalanceTransaction::query()->firstOrFail();
        $this->assertSame(BalanceTransactionType::VipPurchase, $entry->type);
        $this->assertSame(-700, $entry->amount_cents);
        $this->assertSame(9300, $entry->balance_after_cents);
        $this->assertSame($purchase->id, $entry->reference_id);
        $this->assertStringContainsString($product->name, $entry->description);
    }

    public function test_extension_starts_at_current_end(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);

        $this->service()->purchase($user, $product, 7);
        $this->service()->purchase($user, $product->fresh(), 30);

        $second = VipPurchase::query()->orderByDesc('id')->firstOrFail();
        $this->assertSame('2026-10-16 12:00:00', $second->starts_at->toDateTimeString());
        $this->assertSame('2026-11-15 12:00:00', $second->ends_at->toDateTimeString());
        $this->assertSame('2026-11-15 12:00:00', $product->fresh()->vip_until->toDateTimeString());
    }

    public function test_failed_purchase_leaves_no_traces(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 3);

        try {
            $this->service()->purchase($user, $product, 7);
            $this->fail('Покупка без денег должна падать');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Недостаточно средств', $e->getMessage());
        }

        $this->assertSame(0, VipPurchase::query()->count());
        $this->assertSame(0, BalanceTransaction::query()->count());
        $this->assertEquals(3, $this->balance($user));
    }

    // ------------------------------------------------------------------ отключение и возврат

    public function test_cancel_refunds_unused_full_days(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);
        $this->service()->purchase($user, $product, 7); // 7 ₽, баланс 93

        Carbon::setTestNow(now()->addDays(2)->addHours(3)); // прошло 2 дня 3 часа → неиспользовано 4 полных дня

        $state = $this->service()->state($product->fresh(), $user);
        $this->assertTrue($state->canCancel);
        $this->assertSame(400, $state->refundCents);

        $refund = $this->service()->cancel($user, $product->fresh());

        $this->assertSame(400, $refund);
        $this->assertEquals(97, $this->balance($user));
        $product->refresh();
        $this->assertFalse($product->is_vip);
        $this->assertNull($product->vip_until);

        $purchase = VipPurchase::query()->firstOrFail();
        $this->assertTrue($purchase->isCancelled());
        $this->assertSame(400, $purchase->refunded_cents);

        $entry = BalanceTransaction::query()->where('type', BalanceTransactionType::VipRefund->value)->firstOrFail();
        $this->assertSame(400, $entry->amount_cents);
        $this->assertSame(9700, $entry->balance_after_cents);
    }

    public function test_refund_uses_price_paid_not_current_price(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);
        $this->service()->purchase($user, $product, 7); // по 1 ₽ за день

        config(['vip.default_price_per_day' => 10]);
        Cache::flush();
        \App\Models\SiteSetting::query()->delete();

        $refund = $this->service()->cancel($user, $product->fresh());

        $this->assertSame(700, $refund); // ни секунды не прошло: вернули всё, что заплатили
    }

    public function test_cancel_after_extension_refunds_both_periods(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);
        $this->service()->purchase($user, $product, 7);
        $this->service()->purchase($user, $product->fresh(), 30);

        Carbon::setTestNow(now()->addDays(3)); // первый период: осталось 4 дня, второй ещё не начался

        $refund = $this->service()->cancel($user, $product->fresh());

        $this->assertSame(400 + 3000, $refund);
        $this->assertSame(2, VipPurchase::query()->where('status', VipPurchase::STATUS_CANCELLED)->count());
    }

    public function test_cancel_without_unused_days_returns_nothing_but_switches_vip_off(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);
        $this->service()->purchase($user, $product, 1);

        Carbon::setTestNow(now()->addHours(5));

        $this->assertSame(0, $this->service()->cancel($user, $product->fresh()));
        $this->assertFalse($product->fresh()->is_vip);
        $this->assertSame(0, BalanceTransaction::query()->where('type', BalanceTransactionType::VipRefund->value)->count());
    }

    public function test_admin_granted_vip_cannot_be_cancelled_by_seller(): void
    {
        [$user, $product] = $this->sellerProduct();
        $this->service()->setByAdmin($product, true, now()->addDays(10));

        $state = $this->service()->state($product->fresh(), $user);
        $this->assertFalse($state->canCancel);

        $this->expectException(RuntimeException::class);
        $this->service()->cancel($user, $product->fresh());
    }

    public function test_cannot_cancel_foreign_product(): void
    {
        [$owner, $product] = $this->sellerProduct(balance: 50);
        $this->service()->purchase($owner, $product, 7);
        $stranger = User::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->service()->cancel($stranger, $product->fresh());
    }

    // ------------------------------------------------------------------ сортировка по сроку

    public function test_expired_vip_flag_does_not_keep_product_on_top_before_scheduler_runs(): void
    {
        [, $fresh] = $this->sellerProduct();
        $expired = Product::factory()->create(['seller_id' => $fresh->seller_id, 'is_vip' => true, 'vip_until' => now()->subHour()]);
        $active = Product::factory()->create(['seller_id' => $fresh->seller_id, 'is_vip' => true, 'vip_until' => now()->addDay()]);
        $permanent = Product::factory()->create(['seller_id' => $fresh->seller_id, 'is_vip' => true, 'vip_until' => null]);

        $ids = Product::query()->vipActive()->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$active->id, $permanent->id], $ids);

        $first = Product::query()->orderByVipFirst()->orderBy('id')->limit(2)->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$active->id, $permanent->id], $first);
        $this->assertNotContains($expired->id, $first);
    }

    // ------------------------------------------------------------------ страницы кабинета

    public function test_vip_page_shows_products_statuses_and_actions(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);
        $this->service()->purchase($user, $product, 7);
        $plain = Product::factory()->create(['seller_id' => $product->seller_id, 'name' => 'Просто товар']);
        $hidden = Product::factory()->create(['seller_id' => $product->seller_id, 'name' => 'Скрытый товар', 'is_active' => false]);

        $this->actingAs($user)->get(route('account.vip.index'))
            ->assertOk()
            ->assertSee('VIP-размещение')
            ->assertSee($product->name)
            ->assertSee('VIP до 16.10.2026')
            ->assertSee('осталось 7 дней')
            ->assertSee('Продлить')
            ->assertSee('Отключить')
            ->assertSee('Просто товар')
            ->assertSee('Включить')
            ->assertSee('Скрытый товар')
            ->assertSee('снят с публикации')
            ->assertSee('История VIP');
    }

    public function test_vip_page_offers_topup_when_nothing_is_affordable(): void
    {
        [$user] = $this->sellerProduct(balance: 0);

        $this->actingAs($user)->get(route('account.vip.index'))
            ->assertOk()
            ->assertSee('не хватает денег')
            ->assertSee(route('account.user.balance.topup', ['amount' => 1]), false);
    }

    public function test_vip_page_requires_shop_and_login(): void
    {
        $this->get(route('account.vip.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())->get(route('account.vip.index'))
            ->assertRedirect(route('account.store.create'));
    }

    public function test_seller_buys_and_cancels_vip_through_pages_with_clear_messages(): void
    {
        [$user, $product] = $this->sellerProduct(balance: 100);

        $this->actingAs($user)->from(route('account.vip.index'))
            ->post(route('account.store.products.vip', $product), ['days' => 30])
            ->assertRedirect(route('account.vip.index'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'VIP включён') && str_contains($m, '08.11.2026') && str_contains($m, '30,00 ₽') && str_contains($m, '70,00 ₽'));

        $this->actingAs($user)->from(route('account.vip.index'))
            ->delete(route('account.store.products.vip.cancel', $product))
            ->assertRedirect(route('account.vip.index'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'отключён') && str_contains($m, '30,00 ₽'));

        $this->assertEquals(100, $this->balance($user));
    }

    public function test_cancel_endpoint_rejects_foreign_product_and_guests(): void
    {
        [$owner, $product] = $this->sellerProduct(balance: 50);
        $this->service()->purchase($owner, $product, 7);
        [$other] = $this->sellerProduct();

        $this->actingAs($other)->delete(route('account.store.products.vip.cancel', $product))->assertForbidden();
        $this->assertTrue($product->fresh()->is_vip);

        auth()->logout();
        $this->delete(route('account.store.products.vip.cancel', $product))->assertRedirect(route('login'));
    }

    public function test_product_page_panel_for_each_state(): void
    {
        // нельзя купить: причина понятна
        [$user, $hidden] = $this->sellerProduct(product: ['is_active' => false]);
        $this->actingAs($user)->get(route('account.store.products.show', $hidden))
            ->assertOk()->assertSee('VIP пока недоступен')->assertSee('снят с публикации');

        // можно купить, но не хватает денег
        [$poor, $product] = $this->sellerProduct(balance: 5);
        $this->actingAs($poor)->get(route('account.store.products.show', $product))
            ->assertOk()->assertSee('Включить VIP')->assertSee('data-shortfall-cents="2500"', false)
            ->assertSee('Как это работает');

        // активный: продление и отключение
        [$rich, $vip] = $this->sellerProduct(balance: 100);
        $this->service()->purchase($rich, $vip, 7);
        $this->actingAs($rich)->get(route('account.store.products.show', $vip))
            ->assertOk()->assertSee('Продлить VIP')->assertSee('Отключить VIP досрочно')->assertSee('осталось 7 дней')
            ->assertSee('Вернём 7,00 ₽');

        // выдан администратором
        [$owner, $granted] = $this->sellerProduct();
        $this->service()->setByAdmin($granted, true, null);
        $this->actingAs($owner)->get(route('account.store.products.show', $granted))
            ->assertOk()->assertSee('VIP выдан администратором')->assertDontSee('Отключить VIP досрочно');
    }

    public function test_topup_page_prefills_amount_from_link_and_ignores_garbage(): void
    {
        $user = User::factory()->create();
        config(['services.webpayment.enabled' => true, 'services.webpayment.merchant_login' => 'x', 'services.webpayment.merchant_pass' => 'y']);

        $this->actingAs($user)->get(route('account.user.balance.topup', ['amount' => '150']))
            ->assertOk()->assertSee('value="150"', false);
        $this->actingAs($user)->get(route('account.user.balance.topup', ['amount' => '"><script>']))
            ->assertOk()->assertDontSee('<script>alert', false)->assertDontSee('value="&quot;', false);
    }
}
