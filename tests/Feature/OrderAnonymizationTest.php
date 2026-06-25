<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderAnonymizationService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAnonymizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('privacy.order_retention_days', 365);
        config()->set('privacy.anonymization_batch_size', 200);
    }

    public function test_expired_completed_order_is_anonymized_without_losing_audit_data(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Completed,
            'completed_at' => now()->subDays(366),
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+37377712345',
            'delivery_address' => 'Тирасполь',
            'customer_comment' => 'Позвонить',
            'product_name' => 'Тестовый товар',
            'unit_price' => 150,
            'quantity' => 2,
        ]);
        $number = $order->number;
        $productName = $order->product_name;

        $this->assertTrue(app(OrderAnonymizationService::class)->anonymize($order));

        $order->refresh();
        $this->assertSame('Удалено', $order->customer_name);
        $this->assertSame('Удалено', $order->customer_phone);
        $this->assertNull($order->delivery_address);
        $this->assertNull($order->customer_comment);
        $this->assertNull($order->source_ip_hash);
        $this->assertNotNull($order->anonymized_at);
        $this->assertSame($number, $order->number);
        $this->assertSame($productName, $order->product_name);
        $this->assertSame('150.00', $order->unit_price);
        $this->assertSame(2, $order->quantity);
        $this->assertSame(OrderStatus::Completed, $order->status);
    }

    public function test_recent_final_and_active_orders_are_not_anonymized(): void
    {
        $recent = Order::factory()->create([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now()->subDays(364),
        ]);
        $active = Order::factory()->create([
            'status' => OrderStatus::Processing,
            'status_changed_at' => now()->subYears(2),
        ]);

        $count = app(OrderAnonymizationService::class)->anonymizeExpired();

        $this->assertSame(0, $count);
        $this->assertNull($recent->fresh()->anonymized_at);
        $this->assertNull($active->fresh()->anonymized_at);
    }

    public function test_already_anonymized_order_is_idempotent(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Completed,
            'completed_at' => now()->subYears(2),
        ]);
        $service = app(OrderAnonymizationService::class);

        $this->assertTrue($service->anonymize($order));
        $anonymizedAt = $order->fresh()->anonymized_at;
        $this->assertFalse($service->anonymize($order));
        $this->assertTrue($anonymizedAt->equalTo($order->fresh()->anonymized_at));
    }

    public function test_batch_limit_is_respected(): void
    {
        Order::factory()->count(3)->create([
            'status' => OrderStatus::Completed,
            'completed_at' => now()->subYears(2),
        ]);

        $this->assertSame(2, app(OrderAnonymizationService::class)->anonymizeExpired(2));
        $this->assertDatabaseCount('orders', 3);
        $this->assertSame(2, Order::query()->whereNotNull('anonymized_at')->count());
    }

    public function test_command_supports_dry_run_and_real_execution(): void
    {
        Order::factory()->create([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now()->subYears(2),
        ]);

        $this->artisan('orders:anonymize-expired --dry-run')
            ->expectsOutput('Будет обезличено заказов: 1')
            ->assertSuccessful();
        $this->assertSame(0, Order::query()->whereNotNull('anonymized_at')->count());

        $this->artisan('orders:anonymize-expired')
            ->expectsOutput('Обезличено заказов: 1')
            ->assertSuccessful();
        $this->assertSame(1, Order::query()->whereNotNull('anonymized_at')->count());
    }

    public function test_anonymization_command_is_scheduled_daily(): void
    {
        $events = app(Schedule::class)->events();
        $event = collect($events)->first(
            fn ($event): bool => str_contains($event->command ?? '', 'orders:anonymize-expired'),
        );

        $this->assertNotNull($event);
        $this->assertSame('0 2 * * *', $event->expression);
    }
}
