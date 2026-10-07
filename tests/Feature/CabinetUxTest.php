<?php

namespace Tests\Feature;

use App\Models\BalanceTopup;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\Seller;
use App\Models\User;
use App\Notifications\AccountNotification;
use App\Services\CabinetDesignManager;
use App\Services\ProductVipService;
use App\Services\WebPayment\WebPaymentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Единообразие сообщений кабинета, переходы без 403 и уведомления о событиях аккаунта.
 */
class CabinetUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @return array<int, array{string}> */
    public static function themes(): array
    {
        return [['legacy'], ['new']];
    }

    /** @dataProvider themes */
    public function test_flash_messages_are_shown_exactly_once_on_every_cabinet_page(string $theme): void
    {
        app(CabinetDesignManager::class)->setActive($theme);

        $seller = Seller::factory()->create();
        $owner = $seller->user;
        $product = Product::factory()->create(['seller_id' => $seller->id]);
        $order = Order::factory()->create(['seller_id' => $seller->id, 'product_id' => $product->id]);

        $pages = [
            ['account.index', []], ['account.user.index', []], ['account.user.edit', []],
            ['account.user.change_password', []], ['account.user.balance.topup', []], ['account.stats', []],
            ['account.store.products.index', []], ['account.store.products.create', []],
            ['account.store.orders.index', []], ['account.store.events.index', []], ['account.store.delete', []],
            ['account.store.edit', ['seller' => $seller]], ['account.store.products.show', ['product' => $product]],
            ['account.store.products.edit', ['product' => $product]], ['account.store.orders.show', ['order' => $order]],
        ];

        foreach ($pages as [$route, $params]) {
            $html = $this->actingAs($owner)
                ->withSession(['success' => 'MARK-OK', 'error' => 'MARK-ERR', 'info' => 'MARK-INFO', 'warning' => 'MARK-WARN'])
                ->get(route($route, $params))
                ->assertOk()
                ->getContent();

            foreach (['MARK-OK', 'MARK-ERR', 'MARK-INFO', 'MARK-WARN'] as $marker) {
                $this->assertSame(1, substr_count($html, $marker), "[$theme] {$route}: {$marker} показан не один раз");
            }
        }
    }

    public function test_vip_purchase_without_money_explains_what_happened(): void
    {
        app(CabinetDesignManager::class)->setActive('legacy');
        $seller = Seller::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        $this->actingAs($seller->user)
            ->from(route('account.store.products.show', $product))
            ->post(route('account.store.products.vip', $product), ['days' => 1])
            ->assertRedirect(route('account.store.products.show', $product));

        $this->actingAs($seller->user)
            ->get(route('account.store.products.show', $product))
            ->assertSee('Недостаточно средств на балансе')
            ->assertSee(route('account.user.balance.topup'), false);
    }

    public function test_user_without_shop_is_redirected_from_orders_instead_of_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('account.store.orders.index'))
            ->assertRedirect(route('account.store.create'))
            ->assertSessionHas('error');
    }

    /** @dataProvider themes */
    public function test_main_page_is_single_overview_without_duplicate_counters(string $theme): void
    {
        app(CabinetDesignManager::class)->setActive($theme);
        $seller = Seller::factory()->create(['phone' => '77123456']);
        Product::factory()->create(['seller_id' => $seller->id]);

        $html = $this->actingAs($seller->user)->get(route('account.index'))
            ->assertOk()
            ->assertSee('77123456')
            ->assertSee('Контакты и соцсети')
            ->assertSee('Пополнить')
            ->assertDontSee('Активные товары')
            ->assertDontSee('>Активные<', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Основная информация'), "[$theme] блок информации дублируется");
    }

    public function test_new_theme_header_has_notification_bell_with_unread_counter(): void
    {
        app(CabinetDesignManager::class)->setActive('new');
        $user = User::factory()->create();
        $user->notify(new AccountNotification('test', 'Заголовок события', 'Текст события'));

        $this->actingAs($user)->get(route('account.user.index'))
            ->assertOk()
            ->assertSee('cabinet-bell__badge', false)
            ->assertSee('Заголовок события')
            ->assertSee('Текст события');
    }

    public function test_paid_topup_notifies_user(): void
    {
        config(['services.webpayment.enabled' => true, 'services.webpayment.merchant_login' => 'x', 'services.webpayment.merchant_pass' => 'y',
            'services.webpayment.currency' => '000', 'services.webpayment.is_test' => false]);
        $user = User::factory()->create();
        $topup = BalanceTopup::query()->create([
            'user_id' => $user->id, 'amount_cents' => 25000, 'currency' => '000', 'status' => 'pending',
            'is_test' => false, 'expires_at' => now()->addMinutes(30),
        ]);
        $this->partialMock(WebPaymentClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getState')->andReturn(['state' => 1, 'sum' => 25000, 'currency' => '000', 'istest' => 0, 'rrn' => '1', 'lastdgt' => '0123']);
        });

        $this->post(route('payments.webpayment.success'), ['invoiceid' => $topup->id]);

        $notification = $user->notifications()->firstOrFail();
        $this->assertSame('Баланс пополнен', $notification->data['subject']);
        $this->assertStringContainsString('250.00', $notification->data['title']);
    }

    public function test_failed_topup_notifies_user(): void
    {
        config(['services.webpayment.enabled' => true, 'services.webpayment.merchant_login' => 'x', 'services.webpayment.merchant_pass' => 'y']);
        $user = User::factory()->create();
        $topup = BalanceTopup::query()->create([
            'user_id' => $user->id, 'amount_cents' => 25000, 'currency' => '000', 'status' => 'pending',
            'is_test' => false, 'expires_at' => now()->addMinutes(30),
        ]);
        $this->partialMock(WebPaymentClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getState')->andReturn(['state' => 3, 'sum' => 25000, 'currency' => '000', 'istest' => 0, 'rrn' => null, 'lastdgt' => null]);
        });

        $this->post(route('payments.webpayment.fail'), ['invoiceid' => $topup->id]);

        $this->assertSame('Пополнение не выполнено', $user->notifications()->firstOrFail()->data['subject']);
    }

    public function test_developer_tip_notifies_recipient(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $admin = User::factory()->create();
        $admin->forceFill(['role_id' => $role->id])->save();
        $user = User::factory()->create();
        $user->forceFill(['balance' => '100.00'])->save();

        $this->actingAs($user)->post(route('account.user.balance.tip'), ['amount' => '40']);

        $notification = $admin->notifications()->firstOrFail();
        $this->assertSame('Вам отправили благодарность', $notification->data['subject']);
        $this->assertStringContainsString('40.00', $notification->data['title']);
    }

    public function test_vip_expiring_soon_warns_once_and_expiry_notifies(): void
    {
        $seller = Seller::factory()->create();
        $owner = $seller->user;
        $product = Product::factory()->create([
            'seller_id' => $seller->id, 'is_vip' => true, 'vip_until' => now()->addHours(10),
        ]);
        $service = app(ProductVipService::class);

        $this->assertSame(1, $service->notifyExpiringSoon());
        $this->assertSame(0, $service->notifyExpiringSoon());
        $this->assertSame('VIP скоро закончится', $owner->notifications()->firstOrFail()->data['subject']);

        $product->forceFill(['vip_until' => now()->subMinute()])->save();
        $this->assertSame(1, $service->expireDue());

        $this->assertSame(
            ['VIP закончился', 'VIP скоро закончится'],
            $owner->notifications()->get()->pluck('data.subject')->sort()->values()->all(),
        );
    }
}
