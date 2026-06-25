<?php

namespace Tests\Feature;

use App\Contracts\TelegramBotClient;
use App\Events\OrderCreated;
use App\Listeners\SendOrderTelegramNotification;
use App\Models\Order;
use App\Models\Seller;
use App\Services\TelegramConnectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class TelegramOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.telegram.bot_username', 'granat_test_bot');
        config()->set('services.telegram.webhook_secret', 'webhook-secret');
    }

    public function test_owner_receives_deep_link_and_webhook_confirms_chat(): void
    {
        $seller = Seller::factory()->create([
            'telegram_chat_id' => null,
            'telegram_verified_at' => null,
        ]);
        $link = app(TelegramConnectionService::class)->createLink($seller);
        parse_str(parse_url($link, PHP_URL_QUERY), $query);

        $telegram = Mockery::mock(TelegramBotClient::class);
        $telegram->shouldReceive('sendMessage')
            ->once()
            ->with('123456', Mockery::type('string'));
        $this->app->instance(TelegramBotClient::class, $telegram);

        $this->postJson(route('webhooks.telegram'), [
            'message' => [
                'text' => '/start '.$query['start'],
                'chat' => ['id' => 123456],
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'webhook-secret',
        ])->assertOk();

        $seller->refresh();
        $this->assertSame('123456', $seller->telegram_chat_id);
        $this->assertNotNull($seller->telegram_verified_at);
        $this->assertTrue($seller->telegram_notifications_enabled);
    }

    public function test_webhook_rejects_wrong_secret_and_expired_code(): void
    {
        $this->postJson(route('webhooks.telegram'), [], [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong',
        ])->assertForbidden();

        $telegram = Mockery::mock(TelegramBotClient::class);
        $telegram->shouldReceive('sendMessage')->once();
        $this->app->instance(TelegramBotClient::class, $telegram);

        $this->postJson(route('webhooks.telegram'), [
            'message' => [
                'text' => '/start expiredcode',
                'chat' => ['id' => 999],
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'webhook-secret',
        ])->assertOk();

        $this->assertDatabaseMissing('sellers', ['telegram_chat_id' => '999']);
    }

    public function test_listener_sends_only_to_verified_enabled_seller(): void
    {
        $order = Order::factory()->create();
        $order->seller->forceFill([
            'telegram_chat_id' => '777',
            'telegram_verified_at' => now(),
            'telegram_notifications_enabled' => true,
        ])->save();
        $telegram = Mockery::mock(TelegramBotClient::class);
        $telegram->shouldReceive('sendMessage')
            ->once()
            ->with('777', Mockery::on(fn (string $message): bool => str_contains($message, $order->number)));

        (new SendOrderTelegramNotification($telegram))->handle(new OrderCreated($order->id));

        $order->seller->forceFill(['telegram_notifications_enabled' => false])->save();
        (new SendOrderTelegramNotification($telegram))->handle(new OrderCreated($order->id));
    }

    public function test_disconnect_removes_chat_and_disables_notifications(): void
    {
        $seller = Seller::factory()->create([
            'telegram_chat_id' => '123',
            'telegram_verified_at' => now(),
            'telegram_notifications_enabled' => true,
        ]);

        $result = app(TelegramConnectionService::class)->disconnect($seller);

        $this->assertNull($result->telegram_chat_id);
        $this->assertNull($result->telegram_verified_at);
        $this->assertFalse($result->telegram_notifications_enabled);
    }

    public function test_http_client_throws_on_telegram_error_without_touching_order(): void
    {
        config()->set('services.telegram.bot_token', 'test-token');
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false], 500),
        ]);
        $order = Order::factory()->create();

        try {
            app(\App\Services\TelegramHttpBotClient::class)->sendMessage('123', 'test');
            $this->fail('Telegram exception was not thrown.');
        } catch (\Illuminate\Http\Client\RequestException) {
            $this->assertDatabaseHas('orders', ['id' => $order->id]);
        }
    }
}
