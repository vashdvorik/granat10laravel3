<?php

namespace Tests\Feature;

use App\Contracts\TelegramBotClient;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\Seller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\TelegramConnectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/** Telegram-уведомления: отдельный пункт меню магазина и сообщения только о новых заказах. */
class TelegramNotificationsPageTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Seller $seller;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram.bot_username', 'BimpmrBot');

        $this->owner = User::factory()->create();
        $this->seller = Seller::factory()->create([
            'user_id' => $this->owner->id,
            'telegram_chat_id' => null,
            'telegram_verified_at' => null,
            'telegram_notifications_enabled' => false,
        ]);
    }

    private function connect(): void
    {
        $this->seller->forceFill([
            'telegram_chat_id' => '777',
            'telegram_verified_at' => now(),
            'telegram_notifications_enabled' => true,
        ])->save();
    }

    // ------------------------------------------------------------------ страница

    public function test_guest_is_sent_to_login_and_user_without_shop_to_shop_creation(): void
    {
        $this->get(route('account.store.telegram'))->assertRedirect();

        $this->actingAs(User::factory()->create())->get(route('account.store.telegram'))
            ->assertRedirect(route('account.store.create'));
    }

    public function test_page_explains_how_to_connect_and_offers_the_connect_button(): void
    {
        $this->actingAs($this->owner)->get(route('account.store.telegram'))
            ->assertOk()
            ->assertSee('Telegram-уведомления')
            ->assertSee('Не подключено')
            ->assertSee('@BimpmrBot')
            ->assertSee('Запустить')
            ->assertSee(route('account.store.telegram.connect'), false)
            ->assertSee('Обновить статус')
            ->assertDontSee('Отключить Telegram')
            // продавцу прямо сказано, что приходит и что нет
            ->assertSee('каждом новом заказе')
            ->assertSee('Сообщений о смене статуса заказа нет');
    }

    public function test_page_shows_connected_state_with_the_disconnect_button(): void
    {
        $this->connect();

        $this->actingAs($this->owner)->get(route('account.store.telegram'))
            ->assertOk()
            ->assertSee('Подключено')
            ->assertSee($this->seller->fresh()->telegram_verified_at->format('d.m.Y H:i'))
            ->assertSee('Отключить Telegram')
            ->assertSee(route('account.store.telegram.disconnect'), false)
            ->assertDontSee('Подключить Telegram');
    }

    public function test_page_tells_when_the_bot_is_not_configured(): void
    {
        config()->set('services.telegram.bot_username', null);

        $this->actingAs($this->owner)->get(route('account.store.telegram'))
            ->assertOk()
            ->assertSee('Telegram-бот пока не настроен администратором')
            ->assertDontSee(route('account.store.telegram.connect'), false);
    }

    public function test_connect_and_disconnect_work_from_the_new_page(): void
    {
        $this->actingAs($this->owner)
            ->from(route('account.store.telegram'))
            ->post(route('account.store.telegram.connect'))
            ->assertRedirectContains('https://t.me/BimpmrBot?start=');

        $this->connect();

        // свежая модель пользователя: в запросе магазин читается заново, как на сайте
        $this->actingAs(User::find($this->owner->id))
            ->from(route('account.store.telegram'))
            ->delete(route('account.store.telegram.disconnect'))
            ->assertRedirect(route('account.store.telegram'))
            ->assertSessionHas('success');

        $this->assertNull($this->seller->fresh()->telegram_verified_at);
    }

    // ------------------------------------------------------------------ меню и настройки магазина

    public function test_menu_has_a_separate_telegram_item_in_the_default_design(): void
    {
        $this->actingAs($this->owner)->get(route('account.index'))
            ->assertOk()
            ->assertSee('href="'.route('account.store.telegram').'"', false)
            ->assertSee('Telegram-уведомления');
    }

    public function test_menu_has_the_telegram_item_in_the_new_design_and_marks_it_active(): void
    {
        config()->set('cabinet_design.default', 'legacy');
        SiteSetting::query()->updateOrCreate(['key' => config('cabinet_design.setting_key')], ['value' => 'new']);
        Cache::forget(config('cabinet_design.cache_key'));

        try {
            $html = $this->actingAs($this->owner)->get(route('account.store.telegram'))
                ->assertOk()
                ->assertSee('cabinet-new', false)
                ->assertSee('href="'.route('account.store.telegram').'"', false)
                ->assertSee('Не подключено')
                ->getContent();

            $this->assertMatchesRegularExpression(
                '#class="active"\s+href="'.preg_quote(route('account.store.telegram'), '#').'"#',
                $html,
                'пункт меню должен подсвечиваться на своей странице'
            );
        } finally {
            config()->set('cabinet_design.default', 'legacy');
            Cache::forget(config('cabinet_design.cache_key'));
        }
    }

    public function test_shop_settings_page_no_longer_holds_the_connection_block_but_links_to_the_page(): void
    {
        $this->actingAs($this->owner)->get(route('account.store.edit', $this->seller))
            ->assertOk()
            ->assertDontSee('store-telegram-connect-form', false)
            ->assertDontSee('store-telegram-disconnect-form', false)
            ->assertSee('href="'.route('account.store.telegram').'"', false);
    }

    // ------------------------------------------------------------------ когда уходит сообщение

    public function test_telegram_message_goes_out_for_a_new_order_but_not_for_a_status_change(): void
    {
        $this->connect();
        $order = Order::factory()->create([
            'seller_id' => $this->seller->id,
            'product_name' => 'Смартфон Test Pro',
            'status' => OrderStatus::New,
        ]);

        $telegram = Mockery::mock(TelegramBotClient::class);
        // ровно одно сообщение за всё время теста: о новом заказе
        $telegram->shouldReceive('sendMessage')
            ->once()
            ->with('777', Mockery::on(fn (string $message): bool => str_contains($message, 'Новый заказ '.$order->number)));
        $this->app->instance(TelegramBotClient::class, $telegram);

        OrderCreated::dispatch($order->id);

        $order->forceFill(['status' => OrderStatus::Accepted])->save();
        OrderStatusChanged::dispatch($order->id, $this->owner->id);
        OrderStatusChanged::dispatch($order->id, null);
    }

    public function test_changing_status_in_the_cabinet_does_not_write_to_telegram(): void
    {
        $this->connect();
        $order = Order::factory()->create(['seller_id' => $this->seller->id, 'status' => OrderStatus::New]);

        $telegram = Mockery::mock(TelegramBotClient::class);
        $telegram->shouldReceive('sendMessage')->never();
        $this->app->instance(TelegramBotClient::class, $telegram);

        $this->actingAs($this->owner)
            ->patch(route('account.store.orders.status', $order), ['status' => OrderStatus::Accepted->value])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(OrderStatus::Accepted, $order->fresh()->status);
    }

    public function test_confirmation_still_connects_the_chat_through_the_webhook(): void
    {
        config()->set('services.telegram.webhook_secret', 'secret');
        $link = app(TelegramConnectionService::class)->createLink($this->seller);
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);

        $telegram = Mockery::mock(TelegramBotClient::class);
        $telegram->shouldReceive('sendMessage')->once();
        $this->app->instance(TelegramBotClient::class, $telegram);

        $this->postJson(route('webhooks.telegram'), [
            'message' => ['text' => '/start '.$query['start'], 'chat' => ['id' => 4242]],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'secret'])->assertOk();

        $this->actingAs($this->owner)->get(route('account.store.telegram'))
            ->assertOk()
            ->assertSee('Подключено');
    }
}
