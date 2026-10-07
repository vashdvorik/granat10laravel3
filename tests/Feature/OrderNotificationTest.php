<?php

namespace Tests\Feature;

use App\Data\OrderCreationData;
use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Listeners\SendNewOrderNotification;
use App\Listeners\SendOrderStatusChangedNotification;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Services\OrderManagementService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_order_dispatches_event_only_for_first_creation(): void
    {
        Event::fake([OrderCreated::class]);
        $product = $this->createOrderableProduct();
        $data = $this->orderData();
        $service = app(OrderService::class);

        $service->create($product, $data);
        $service->create($product, $data);

        Event::assertDispatchedTimes(OrderCreated::class, 1);
    }

    public function test_new_order_listener_notifies_store_owner(): void
    {
        $order = Order::factory()->create();
        $owner = $order->seller->user;

        app(SendNewOrderNotification::class)->handle(new OrderCreated($order->id));

        $notification = $owner->notifications()->sole();
        $this->assertSame('order_created', $notification->data['type']);
        $this->assertSame($order->number, $notification->data['subject']);
        $this->assertSame(route('account.store.orders.show', $order), $notification->data['url']);
    }

    public function test_status_change_by_administrator_notifies_owner(): void
    {
        Event::fake([OrderStatusChanged::class]);
        $order = Order::factory()->create(['status' => OrderStatus::New]);
        $administrator = User::factory()->create();

        app(OrderManagementService::class)->transition(
            $order,
            OrderStatus::Accepted,
            $administrator,
        );

        Event::assertDispatched(OrderStatusChanged::class, function (OrderStatusChanged $event) use ($order, $administrator): bool {
            return $event->orderId === $order->id && $event->actorId === $administrator->id;
        });

        app(SendOrderStatusChangedNotification::class)->handle(
            new OrderStatusChanged($order->id, $administrator->id),
        );

        $this->assertSame('order_status_changed', $order->seller->user->notifications()->sole()->data['type']);
    }

    public function test_owner_is_not_notified_about_own_status_change(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Accepted]);
        $owner = $order->seller->user;

        app(SendOrderStatusChangedNotification::class)->handle(
            new OrderStatusChanged($order->id, $owner->id),
        );

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_order_notification_opens_owned_order(): void
    {
        $order = Order::factory()->create();
        $owner = $order->seller->user;
        app(SendNewOrderNotification::class)->handle(new OrderCreated($order->id));
        $notification = $owner->notifications()->sole();

        $this->actingAs($owner)
            ->get(route('account.notifications.read', $notification))
            ->assertRedirect(route('account.store.orders.show', $order));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    private function createOrderableProduct(): Product
    {
        $seller = Seller::factory()->create(['can_order' => true]);

        return Product::factory()->create([
            'seller_id' => $seller->id,
            'can_order' => true,
        ]);
    }

    private function orderData(): OrderCreationData
    {
        return new OrderCreationData(
            customerName: 'Покупатель',
            customerPhone: '+373 777 12 345',
            quantity: 1,
            fulfillmentMethod: 'pickup',
            deliveryAddress: null,
            customerComment: null,
            consentVersion: 'test',
            idempotencyKey: (string) Str::uuid(),
            sourceIp: '127.0.0.1',
        );
    }
}
