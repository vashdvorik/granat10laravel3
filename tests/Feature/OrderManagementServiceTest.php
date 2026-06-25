<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_invalid_transition_does_not_change_order(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::New]);

        try {
            app(OrderManagementService::class)->transition(
                $order,
                OrderStatus::Completed,
                $order->seller->user,
            );
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame(OrderStatus::New, $order->fresh()->status);
        $this->assertDatabaseCount('order_status_history', 0);
    }

    public function test_final_status_sets_timestamp_and_cannot_transition_further(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Processing]);
        $service = app(OrderManagementService::class);

        $completed = $service->transition(
            $order,
            OrderStatus::Completed,
            $order->seller->user,
        );

        $this->assertNotNull($completed->completed_at);
        $this->expectException(ValidationException::class);

        $service->transition($completed, OrderStatus::Cancelled, $order->seller->user);
    }
}
