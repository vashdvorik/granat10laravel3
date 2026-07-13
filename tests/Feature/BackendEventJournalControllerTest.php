<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Package;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendEventJournalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_event_journal_shows_package_activity_and_order_history(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $package = Package::factory()->create(['name' => 'Start']);
        $order = Order::factory()->create([
            'seller_id' => $seller->id,
            'status' => OrderStatus::Accepted,
        ]);

        activity('packages')
            ->causedBy($user)
            ->performedOn($package)
            ->withProperties([
                'seller_id' => $seller->id,
                'package_name' => 'Start',
            ])
            ->log('Package activated for test');

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
            ->assertSee('Package activated for test')
            ->assertSee($order->number)
            ->assertSee('Customer contacted');
    }

    public function test_seller_event_journal_does_not_show_foreign_events(): void
    {
        $user = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $user->id]);
        $foreignUser = User::factory()->create();
        $foreignSeller = Seller::factory()->create(['user_id' => $foreignUser->id]);
        $package = Package::factory()->create();
        $ownOrder = Order::factory()->create(['seller_id' => $seller->id]);
        $foreignOrder = Order::factory()->create(['seller_id' => $foreignSeller->id]);

        activity('packages')
            ->causedBy($user)
            ->performedOn($package)
            ->withProperties(['seller_id' => $seller->id])
            ->log('Own package event');

        activity('packages')
            ->causedBy($foreignUser)
            ->performedOn($package)
            ->withProperties(['seller_id' => $foreignSeller->id])
            ->log('Foreign package event');

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
            ->assertSee('Own package event')
            ->assertSee('Own order event')
            ->assertDontSee('Foreign package event')
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
