<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_sees_only_own_orders(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $ownOrder = Order::factory()->create(['seller_id' => $seller->id]);
        $foreignOrder = Order::factory()->create();

        $this->actingAs($user)
            ->get(route('account.store.orders.index'))
            ->assertOk()
            ->assertSee($ownOrder->number)
            ->assertDontSee($foreignOrder->number);
    }

    public function test_seller_cannot_open_foreign_order(): void
    {
        $user = User::factory()->create();
        Seller::factory()->create(['user_id' => $user->id]);
        $foreignOrder = Order::factory()->create();

        $this->actingAs($user)
            ->get(route('account.store.orders.show', $foreignOrder))
            ->assertForbidden();
    }

    public function test_seller_can_filter_orders_by_status(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $newOrder = Order::factory()->create([
            'seller_id' => $seller->id,
            'status' => OrderStatus::New,
        ]);
        $acceptedOrder = Order::factory()->create([
            'seller_id' => $seller->id,
            'status' => OrderStatus::Accepted,
        ]);

        $this->actingAs($user)
            ->get(route('account.store.orders.index', ['status' => OrderStatus::Accepted->value]))
            ->assertOk()
            ->assertSee($acceptedOrder->number)
            ->assertDontSee($newOrder->number);
    }

    public function test_owner_can_update_order_status(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'seller_id' => $seller->id,
            'status' => OrderStatus::New,
        ]);

        $this->actingAs($user)
            ->patch(route('account.store.orders.status', $order), [
                'status' => OrderStatus::Accepted->value,
                'comment' => 'Связались с покупателем.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(OrderStatus::Accepted, $order->fresh()->status);
    }

    public function test_owner_cannot_skip_order_status(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'seller_id' => $seller->id,
            'status' => OrderStatus::New,
        ]);

        $this->actingAs($user)
            ->patch(route('account.store.orders.status', $order), [
                'status' => OrderStatus::Completed->value,
            ])
            ->assertSessionHasErrors(['status']);

        $this->assertSame(OrderStatus::New, $order->fresh()->status);
    }
}
