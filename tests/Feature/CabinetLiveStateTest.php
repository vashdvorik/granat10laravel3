<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductModerationDecisionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Статус товара и колокольчик обновляются сами: страница спрашивает «что изменилось» и подтягивает свежие блоки.
 * Здесь проверяется серверная часть (ответ account/live) и то, что страницы отдают скрипту нужную разметку.
 */
class CabinetLiveStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login(): void
    {
        $this->getJson(route('account.live'))->assertUnauthorized();
    }

    public function test_without_a_product_it_only_reports_notifications_and_a_slow_poll(): void
    {
        $user = Product::factory()->create()->seller->user;

        $this->actingAs($user)
            ->getJson(route('account.live'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJson(['notifications' => '0:', 'poll' => 60])
            ->assertJsonMissingPath('product');
    }

    public function test_it_returns_the_signature_that_the_page_was_rendered_with(): void
    {
        $product = Product::factory()->create(['moderation_status' => ProductModerationStatus::Approved]);

        $response = $this->actingAs($product->seller->user)
            ->getJson(route('account.live', ['product' => $product->id]))
            ->assertOk()
            ->assertJsonPath('product.id', $product->id);

        $signature = $response->json('product.signature');

        // Подпись совпадает с той, что положена на страницы товара: иначе страница обновлялась бы бесконечно
        $this->assertSame($product->fresh()->cabinet_state_signature, $signature);
        $this->actingAs($product->seller->user)->get(route('account.store.products.show', $product))
            ->assertSee('data-live-signature="'.$signature.'"', false);
        $this->actingAs($product->seller->user)->get(route('account.store.products.edit', $product))
            ->assertSee('data-live-signature="'.$signature.'"', false);
    }

    public function test_signature_changes_when_moderation_finishes(): void
    {
        $product = Product::factory()->pendingModeration()->create();
        $user = $product->seller->user;

        $before = $this->actingAs($user)->getJson(route('account.live', ['product' => $product->id]))->json('product.signature');

        $product->forceFill([
            'moderation_status' => ProductModerationStatus::Approved,
            'moderated_version' => $product->moderation_version,
        ])->save();

        $after = $this->actingAs($user)->getJson(route('account.live', ['product' => $product->id]))->json('product.signature');

        $this->assertNotSame($before, $after);
    }

    public function test_poll_interval_depends_on_how_soon_the_status_can_change(): void
    {
        $user = Product::factory()->create()->seller->user;
        $product = Product::factory()->create(['seller_id' => $user->seller->id]);

        $expected = [
            ProductModerationStatus::Pending->value => 8,
            ProductModerationStatus::Processing->value => 8,
            ProductModerationStatus::NeedsReview->value => 30,
            ProductModerationStatus::Failed->value => 30,
            ProductModerationStatus::Approved->value => 60,
            ProductModerationStatus::Rejected->value => 60,
            ProductModerationStatus::ChangesRequired->value => 60,
            ProductModerationStatus::Draft->value => 60,
        ];

        foreach ($expected as $status => $seconds) {
            $product->forceFill(['moderation_status' => $status])->save();

            $this->actingAs($user)
                ->getJson(route('account.live', ['product' => $product->id]))
                ->assertJsonPath('poll', $seconds);
        }
    }

    public function test_someone_elses_product_is_not_revealed(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(Product::factory()->create()->seller->user)
            ->getJson(route('account.live', ['product' => $product->id]))
            ->assertOk()
            ->assertJsonMissingPath('product');

        $this->actingAs(User::factory()->create())
            ->getJson(route('account.live', ['product' => 999999]))
            ->assertOk()
            ->assertJsonMissingPath('product');
    }

    public function test_notification_token_changes_with_new_and_read_notifications(): void
    {
        $product = Product::factory()->create();
        $user = $product->seller->user;

        $empty = $this->actingAs($user)->getJson(route('account.live'))->json('notifications');

        $user->notify(new ProductModerationDecisionNotification($product));
        $one = $this->actingAs($user)->getJson(route('account.live'))->json('notifications');

        $user->unreadNotifications->markAsRead();
        $read = $this->actingAs($user)->getJson(route('account.live'))->json('notifications');

        $this->assertSame('0:', $empty);
        $this->assertStringStartsWith('1:', $one);
        $this->assertStringStartsWith('0:', $read);
        $this->assertNotSame($one, $read);
    }

    public function test_bell_in_the_page_carries_the_same_token_as_the_endpoint(): void
    {
        $product = Product::factory()->create();
        $user = $product->seller->user;
        $user->notify(new ProductModerationDecisionNotification($product));
        $user->notify(new ProductModerationDecisionNotification($product));

        $token = $this->actingAs($user)->getJson(route('account.live'))->json('notifications');

        // Разные токены в странице и в ответе сервера заставили бы страницу подтягиваться заново каждые полминуты
        $this->actingAs($user)->get(route('account.index'))
            ->assertSee('data-live-notifications="'.$token.'"', false)
            ->assertSee('data-live-region="notifications:list"', false)
            ->assertSee('data-live-url="'.route('account.live').'"', false);
    }

    public function test_product_pages_mark_the_blocks_that_can_be_replaced(): void
    {
        $product = Product::factory()->create();
        $user = $product->seller->user;

        $show = $this->actingAs($user)->get(route('account.store.products.show', $product))->assertOk();
        foreach (['status', 'primary-action', 'actions', 'vip'] as $region) {
            $show->assertSee('data-live-region="product:'.$region.'"', false);
        }
        $show->assertSee('data-live-product="'.$product->id.'"', false)->assertSee('data-live-reload', false);

        $edit = $this->actingAs($user)->get(route('account.store.products.edit', $product))->assertOk();
        foreach (['status', 'hint', 'header-action', 'form-actions'] as $region) {
            $edit->assertSee('data-live-region="product:'.$region.'"', false);
        }
        // Со страницы редактирования нельзя перезагружаться: пропал бы несохранённый ввод
        $edit->assertSee('data-live-sync="data-recheck-mode"', false)->assertDontSee('data-live-reload', false);
    }

    public function test_product_list_polls_faster_while_a_check_is_running(): void
    {
        $product = Product::factory()->pendingModeration()->create();

        $this->actingAs($product->seller->user)
            ->get(route('account.store.products.index'))
            ->assertOk()
            ->assertSee('data-card-state="queued"', false);
    }
}
