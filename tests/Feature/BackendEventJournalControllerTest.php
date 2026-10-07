<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendEventJournalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_event_journal_shows_product_activity_and_order_history(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'seller_id' => $seller->id,
            'status' => OrderStatus::Accepted,
        ]);

        $product = Product::factory()->create(['seller_id' => $seller->id]);

        activity('products')
            ->causedBy($user)
            ->performedOn($product)
            ->withProperties(['seller_id' => $seller->id])
            ->log('Product activity for test');

        OrderStatusHistory::factory()->create([
            'order_id' => $order->id,
            'from_status' => OrderStatus::New,
            'to_status' => OrderStatus::Accepted,
            'comment' => 'Customer contacted',
        ]);

        $this->actingAs($user)
            ->get(route('account.store.events.index'))
            ->assertOk()
            ->assertSee('events-page', false)
            ->assertSee('event-timeline__item', false)
            ->assertSee('Product activity for test')
            ->assertSee($order->number)
            ->assertSee('Customer contacted');
    }

    public function test_seller_event_journal_does_not_show_foreign_events(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $foreignUser = User::factory()->create();
        $foreignSeller = Seller::factory()->create(['user_id' => $foreignUser->id]);
        $ownOrder = Order::factory()->create(['seller_id' => $seller->id]);
        $foreignOrder = Order::factory()->create(['seller_id' => $foreignSeller->id]);

        $ownProduct = Product::factory()->create(['seller_id' => $seller->id]);
        $foreignProduct = Product::factory()->create(['seller_id' => $foreignSeller->id]);

        activity('products')
            ->causedBy($user)
            ->performedOn($ownProduct)
            ->log('Own product event');

        activity('products')
            ->causedBy($foreignUser)
            ->performedOn($foreignProduct)
            ->log('Foreign product event');

        OrderStatusHistory::factory()->create([
            'order_id' => $ownOrder->id,
            'to_status' => OrderStatus::Accepted,
            'comment' => 'Own order event',
        ]);

        OrderStatusHistory::factory()->create([
            'order_id' => $foreignOrder->id,
            'to_status' => OrderStatus::Accepted,
            'comment' => 'Foreign order event',
        ]);

        $this->actingAs($user)
            ->get(route('account.store.events.index'))
            ->assertOk()
            ->assertSee('Own product event')
            ->assertSee('Own order event')
            ->assertDontSee('Foreign product event')
            ->assertDontSee('Foreign order event');
    }

    public function test_user_without_store_is_redirected_from_event_journal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.store.events.index'))
            ->assertRedirect(route('account.store.create'));
    }
}
