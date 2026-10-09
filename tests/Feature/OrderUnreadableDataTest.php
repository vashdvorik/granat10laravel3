<?php

namespace Tests\Feature;

use App\Casts\SafeEncrypted;
use App\Contracts\TelegramBotClient;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Listeners\SendOrderTelegramNotification;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Seller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\OrderAnonymizationService;
use Filament\Facades\Filament;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Заказ, чьи личные данные не расшифровываются (сменился APP_KEY), не должен ронять страницы:
 * показываем всё, что читается, а нечитаемое заменяем на «Данные недоступны».
 */
class OrderUnreadableDataTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Seller $seller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->seller = Seller::factory()->create(['user_id' => $this->owner->id]);
    }

    /** Заказ, зашифрованный «чужим» ключом: при чтении выдаёт «The MAC is invalid». */
    private function brokenOrder(array $attributes = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'seller_id' => $this->seller->id,
            'fulfillment_method' => 'delivery',
        ], $attributes));

        $foreign = new Encrypter(str_repeat('k', 32), 'AES-256-CBC');
        DB::table('orders')->where('id', $order->id)->update([
            'customer_name' => $foreign->encryptString('Иван'),
            'customer_phone' => $foreign->encryptString('+37377700000'),
            'delivery_address' => $foreign->encryptString('ул. Ленина'),
            'customer_comment' => $foreign->encryptString('позвоните'),
        ]);

        return $order->refresh();
    }

    private function healthyOrder(): Order
    {
        return Order::factory()->create([
            'seller_id' => $this->seller->id,
            'customer_name' => 'Мария Здоровая',
            'customer_phone' => '+37377711111',
        ]);
    }

    // ------------------------------------------------------------------ само поле

    public function test_cast_is_compatible_with_laravel_encrypted_format_in_both_directions(): void
    {
        $order = Order::factory()->create(['customer_name' => 'Иван Петров', 'delivery_address' => null]);

        // записано полем SafeEncrypted — читается обычным Crypt и встроенным encrypted
        $raw = DB::table('orders')->where('id', $order->id)->value('customer_name');
        $this->assertSame('Иван Петров', Crypt::decryptString($raw));

        // записано по-старому (встроенный encrypted = encryptString) — читается новым полем
        DB::table('orders')->where('id', $order->id)->update(['customer_name' => encrypt('Старая запись', false)]);
        $this->assertSame('Старая запись', Order::find($order->id)->customer_name);

        $this->assertNull(Order::find($order->id)->delivery_address);
    }

    public function test_unreadable_values_become_a_placeholder_instead_of_an_exception(): void
    {
        $order = $this->brokenOrder();

        $this->assertSame(SafeEncrypted::UNREADABLE, $order->customer_name);
        $this->assertSame(SafeEncrypted::UNREADABLE, $order->customer_phone);
        $this->assertTrue($order->hasUnreadableData());
        $this->assertTrue($order->isUnreadable('customer_phone'));

        // значение, записанное без шифрования, тоже не ломает чтение
        DB::table('orders')->where('id', $order->id)->update(['customer_name' => 'открытый текст']);
        $this->assertSame(SafeEncrypted::UNREADABLE, Order::find($order->id)->customer_name);

        $this->assertFalse($this->healthyOrder()->hasUnreadableData());
    }

    public function test_failure_is_logged_once_per_field_without_personal_data(): void
    {
        $order = $this->brokenOrder();
        $fresh = Order::find($order->id);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $context['id'] === $order->id
                && $context['field'] === 'customer_name'
                && ! str_contains(json_encode($context, JSON_UNESCAPED_UNICODE), 'Иван'));

        $fresh->customer_name;
        $fresh->customer_name;
        $fresh->customer_name;
    }

    // ------------------------------------------------------------------ кабинет продавца

    public function test_cabinet_orders_list_renders_with_a_broken_order(): void
    {
        $broken = $this->brokenOrder();
        $healthy = $this->healthyOrder();

        $this->actingAs($this->owner)->get(route('account.store.orders.index'))
            ->assertOk()
            ->assertSee($broken->number)
            ->assertSee('Данные покупателя недоступны')
            ->assertSee($healthy->number)
            ->assertSee('Мария Здоровая');
    }

    public function test_cabinet_order_page_renders_without_a_phone_link_for_unreadable_phone(): void
    {
        $broken = $this->brokenOrder();
        $healthy = $this->healthyOrder();

        $this->actingAs($this->owner)->get(route('account.store.orders.show', $broken))
            ->assertOk()
            ->assertSee($broken->number)
            ->assertSee($broken->product_name)
            ->assertSee('Данные покупателя не удалось прочитать')
            ->assertSee(SafeEncrypted::UNREADABLE)
            ->assertDontSee('href="tel:"', false);

        $this->actingAs($this->owner)->get(route('account.store.orders.show', $healthy))
            ->assertOk()
            ->assertSee('href="tel:37377711111"', false)
            ->assertDontSee('Данные покупателя не удалось прочитать');
    }

    public function test_the_new_cabinet_design_also_survives_a_broken_order(): void
    {
        config()->set('cabinet_design.default', 'legacy');
        SiteSetting::query()->updateOrCreate(['key' => config('cabinet_design.setting_key')], ['value' => 'new']);
        Cache::forget(config('cabinet_design.cache_key'));
        $broken = $this->brokenOrder();

        try {
            $this->actingAs($this->owner)->get(route('account.store.orders.index'))
                ->assertOk()
                ->assertSee('cabinet-new', false)
                ->assertSee($broken->number)
                ->assertSee('Данные покупателя недоступны');

            $this->actingAs($this->owner)->get(route('account.store.orders.show', $broken))
                ->assertOk()
                ->assertSee('Данные покупателя не удалось прочитать')
                ->assertDontSee('href="tel:"', false);
        } finally {
            config()->set('cabinet_design.default', 'legacy');
            Cache::forget(config('cabinet_design.cache_key'));
        }
    }

    public function test_status_of_a_broken_order_can_still_be_changed(): void
    {
        $broken = $this->brokenOrder();

        $this->actingAs($this->owner)
            ->patch(route('account.store.orders.status', $broken), [
                'status' => OrderStatus::Accepted->value,
                'comment' => 'Приняли.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(OrderStatus::Accepted, $broken->fresh()->status);
    }

    // ------------------------------------------------------------------ остальные места

    public function test_telegram_notification_is_sent_with_a_placeholder_for_a_broken_order(): void
    {
        $broken = $this->brokenOrder(['product_name' => 'Смартфон Test Pro']);
        $this->seller->forceFill([
            'telegram_chat_id' => '777',
            'telegram_verified_at' => now(),
            'telegram_notifications_enabled' => true,
        ])->save();

        $telegram = Mockery::mock(TelegramBotClient::class);
        $telegram->shouldReceive('sendMessage')
            ->once()
            ->with('777', Mockery::on(fn (string $message): bool => str_contains($message, $broken->number)
                && str_contains($message, 'Смартфон Test Pro')
                && str_contains($message, SafeEncrypted::UNREADABLE)
                && ! str_contains($message, 'Иван')));

        (new SendOrderTelegramNotification($telegram))->handle(new OrderCreated($broken->id));
    }

    public function test_admin_orders_pages_render_with_a_broken_order(): void
    {
        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
        $role = Role::query()->create(['name' => 'admin-'.uniqid(), 'display_name' => 'Администратор']);
        $role->permissions()->attach(Permission::query()->firstOrCreate(['key' => 'browse_admin'], ['table_name' => null]));
        $admin = User::factory()->create(['role_id' => $role->id]);
        $broken = $this->brokenOrder();
        $healthy = $this->healthyOrder();

        $this->actingAs($admin);

        Livewire::test(ListOrders::class)->assertCanSeeTableRecords([$broken, $healthy]);
        Livewire::test(ViewOrder::class, ['record' => $broken->getRouteKey()])->assertOk();
    }

    public function test_retention_anonymization_still_works_for_a_broken_order(): void
    {
        config()->set('privacy.order_retention_days', 365);
        $broken = $this->brokenOrder([
            'status' => OrderStatus::Completed,
            'completed_at' => now()->subDays(400),
        ]);

        $this->assertTrue(app(OrderAnonymizationService::class)->anonymize($broken));

        $fixed = Order::find($broken->id);
        $this->assertSame('Удалено', $fixed->customer_name);
        $this->assertFalse($fixed->hasUnreadableData());
        $this->assertNotNull($fixed->anonymized_at);
    }
}
