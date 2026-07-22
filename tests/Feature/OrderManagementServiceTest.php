<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_transition_updates_order_and_writes_history(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::New]);
        $actor = $order->seller->user;

        $updated = app(OrderManagementService::class)->transition(
            $order,
            OrderStatus::Accepted,
            $actor,
            'Заказ подтверждён.',
        );

        $this->assertSame(OrderStatus::Accepted, $updated->status);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::New->value,
            'to_status' => OrderStatus::Accepted->value,
            'changed_by_user_id' => $actor->id,
            'comment' => 'Заказ подтверждён.',
        ]);
    }

    public function test_transition_can_set_any_status_and_writes_history(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::New]);

        $updated = app(OrderManagementService::class)->transition(
            $order,
            OrderStatus::Completed,
            $order->seller->user,
        );

        $this->assertSame(OrderStatus::Completed, $updated->status);
        $this->assertNotNull($updated->completed_at);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::New->value,
            'to_status' => OrderStatus::Completed->value,
        ]);
    }

    public function test_final_status_sets_timestamp_and_can_be_changed_later(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Processing]);
        $service = app(OrderManagementService::class);

        $completed = $service->transition(
            $order,
            OrderStatus::Completed,
            $order->seller->user,
        );

        $this->assertNotNull($completed->completed_at);

        $cancelled = $service->transition($completed, OrderStatus::Cancelled, $order->seller->user);

        $this->assertSame(OrderStatus::Cancelled, $cancelled->status);
        $this->assertNull($cancelled->completed_at);
        $this->assertNotNull($cancelled->cancelled_at);
        $this->assertDatabaseCount('order_status_history', 2);
    }
}
