<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductModerationDecisionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CabinetNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_and_mark_own_notification_as_read(): void
    {
        $product = Product::factory()->create([
            'moderation_status' => ProductModerationStatus::Approved,
        ]);
        $user = $product->seller->user;
        $user->notify(new ProductModerationDecisionNotification($product));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->get(route('account.notifications.read', $notification->id))
            ->assertRedirect(route('account.store.products.show', $product));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $product = Product::factory()->create();
        $owner = $product->seller->user;
        $owner->notify(new ProductModerationDecisionNotification($product));
        $notification = $owner->notifications()->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get(route('account.notifications.read', $notification->id))
            ->assertNotFound();
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $product = Product::factory()->create();
        $user = $product->seller->user;
        $user->notify(new ProductModerationDecisionNotification($product));
        $user->notify(new ProductModerationDecisionNotification($product));

        $this->actingAs($user)
            ->from(route('account.index'))
            ->post(route('account.notifications.read-all'))
            ->assertRedirect(route('account.index'));

        $this->assertSame(0, $user->unreadNotifications()->count());
    }
}
