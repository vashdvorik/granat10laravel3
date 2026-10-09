<?php

namespace Tests\Feature;

use App\Enums\ProductDisplayState;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ProductCabinetStateService;
use App\Services\ProductStateTransitionService;
use App\Services\ProductStatusStoryFactory;
use App\Services\ProductViewStateFactory;
use App\Services\ProductVipService;
use App\Services\SellerCabinetStateService;
use App\Services\SellerEventTimelineService;
use App\Services\SellerVisibilityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/** «Скрыть продавца»: магазин и товары пропадают с сайта, продавец видит причину красным блоком в кабинете. */
class SellerHiddenTest extends TestCase
{
    use RefreshDatabase;

    private const REASON = 'Название магазина нарушает правила площадки. Измените название и напишите в поддержку.';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    private function sellerWithProduct(array $sellerOverrides = [], array $productOverrides = []): array
    {
        $seller = Seller::factory()->create(array_merge(['alias' => 'bad-shop', 'description' => null], $sellerOverrides));
        $category = Category::factory()->create(['alias' => 'mice-'.uniqid()]);
        $product = Product::factory()->create(array_merge([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'alias' => 'mouse-1',
            'name' => 'Мышь для проверки скрытия',
        ], $productOverrides));
        Image::factory()->main()->create(['product_id' => $product->id]);

        return [$seller, $product, $category];
    }

    private function hide(Seller $seller, string $reason = self::REASON): Seller
    {
        $seller->forceFill(['hidden_at' => now(), 'hidden_reason' => $reason])->save();

        return $seller;
    }

    private function administrator(): User
    {
        $role = \App\Models\Role::query()->create(['name' => 'admin-'.uniqid(), 'display_name' => 'Администратор']);
        $role->permissions()->attach(\App\Models\Permission::query()->firstOrCreate(['key' => 'browse_admin'], ['table_name' => null]));

        return User::factory()->create(['role_id' => $role->id]);
    }

    // ------------------------------------------------------------------ сервис

    public function test_administrator_hides_seller_with_reason(): void
    {
        [$seller] = $this->sellerWithProduct();
        $admin = $this->administrator();

        app(SellerVisibilityService::class)->hide($admin, $seller, '  '.self::REASON.'  ');

        $seller->refresh();
        $this->assertTrue($seller->isHidden());
        $this->assertSame(self::REASON, $seller->hidden_reason);
        $this->assertSame($admin->id, $seller->hidden_by_user_id);
        $this->assertNotNull($seller->hidden_at);

        $log = Activity::query()->where('description', 'Магазин скрыт администратором')->first();
        $this->assertNotNull($log);
        $this->assertSame(self::REASON, $log->properties->get('reason'));
        $this->assertSame($admin->id, $log->causer_id);
    }

    public function test_reason_is_required(): void
    {
        [$seller] = $this->sellerWithProduct();

        foreach (['', '   '] as $blank) {
            try {
                app(SellerVisibilityService::class)->hide($this->administrator(), $seller, $blank);
                $this->fail('Ожидалась ошибка валидации');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('reason', $exception->errors());
            }
        }

        $this->assertFalse($seller->refresh()->isHidden());
    }

    public function test_reason_has_a_length_limit(): void
    {
        [$seller] = $this->sellerWithProduct();

        $this->expectException(ValidationException::class);

        app(SellerVisibilityService::class)->hide(
            $this->administrator(),
            $seller,
            str_repeat('а', SellerVisibilityService::MAX_REASON_LENGTH + 1)
        );
    }

    public function test_owner_cannot_hide_or_show_his_own_shop(): void
    {
        [$seller] = $this->sellerWithProduct();

        $this->expectException(AuthorizationException::class);

        app(SellerVisibilityService::class)->hide($seller->user, $seller, self::REASON);
    }

    public function test_hiding_again_updates_reason_and_keeps_original_date(): void
    {
        [$seller] = $this->sellerWithProduct();
        $admin = $this->administrator();
        $service = app(SellerVisibilityService::class);

        $this->travelTo(now()->subDay());
        $service->hide($admin, $seller, 'Первая причина');
        $firstDate = $seller->refresh()->hidden_at;
        $this->travelBack();

        $service->hide($admin, $seller, 'Уточнённая причина');

        $seller->refresh();
        $this->assertSame('Уточнённая причина', $seller->hidden_reason);
        $this->assertTrue($seller->hidden_at->equalTo($firstDate));
        $this->assertTrue(Activity::query()->where('description', 'Причина скрытия магазина изменена')->exists());
    }

    public function test_show_returns_seller_and_clears_reason(): void
    {
        [$seller] = $this->sellerWithProduct();
        $this->hide($seller);
        $seller->forceFill(['hidden_by_user_id' => $this->administrator()->id])->save();

        app(SellerVisibilityService::class)->show($this->administrator(), $seller);

        $seller->refresh();
        $this->assertFalse($seller->isHidden());
        $this->assertNull($seller->hidden_reason);
        $this->assertNull($seller->hidden_by_user_id);

        $log = Activity::query()->where('description', 'Магазин снова показан покупателям')->first();
        $this->assertNotNull($log);
        $this->assertSame(self::REASON, $log->properties->get('previous_reason'));
    }

    public function test_show_on_visible_seller_changes_nothing(): void
    {
        [$seller] = $this->sellerWithProduct();

        app(SellerVisibilityService::class)->show($this->administrator(), $seller);

        $this->assertFalse(Activity::query()->where('description', 'Магазин снова показан покупателям')->exists());
    }

    // ------------------------------------------------------------------ публичный сайт

    public function test_hidden_shop_page_and_product_page_are_not_found_until_shown_again(): void
    {
        [$seller, $product] = $this->sellerWithProduct();

        $this->get(route('seller', 'bad-shop'))->assertOk();
        $this->get(route('product.show', 'mouse-1'))->assertOk();

        app(SellerVisibilityService::class)->hide($this->administrator(), $seller, self::REASON);

        $this->get(route('seller', 'bad-shop'))->assertNotFound();
        $this->get(route('product.show', 'mouse-1'))->assertNotFound();

        app(SellerVisibilityService::class)->show($this->administrator(), $seller);

        $this->get(route('seller', 'bad-shop'))->assertOk();
        $this->get(route('product.show', 'mouse-1'))->assertOk();
    }

    public function test_hidden_shop_is_missing_from_catalog_home_and_shops_list(): void
    {
        [$seller, , $category] = $this->sellerWithProduct();
        $emptyShop = Seller::factory()->create(['name' => 'Магазин без товаров', 'alias' => 'empty-shop', 'description' => null]);
        // Пока магазин виден, товар в каталоге и на главной есть: иначе проверка ниже ничего не доказывает
        $this->get(route('catalog.show', $category->alias))->assertSee('Мышь для проверки скрытия');
        $this->get(route('home'))->assertSee('Мышь для проверки скрытия');
        $this->get(route('shops'))->assertSee('Магазин без товаров');

        $this->hide($seller, self::REASON);
        $this->hide($emptyShop, 'Неприличное название');
        Cache::flush();

        $this->get(route('catalog.show', $category->alias))->assertDontSee('Мышь для проверки скрытия');
        $this->get(route('home'))->assertDontSee('Мышь для проверки скрытия');

        // Магазин без единого товара тоже не должен висеть в списке магазинов
        $this->get(route('shops'))
            ->assertOk()
            ->assertDontSee('Магазин без товаров')
            ->assertDontSee(e($seller->name));
    }

    public function test_visible_scope_and_sitemap_skip_hidden_sellers(): void
    {
        [$seller] = $this->sellerWithProduct();
        [, $other] = $this->sellerWithProduct(['alias' => 'good-shop'], ['alias' => 'mouse-2']);

        $this->assertSame(2, Product::visible()->count());
        $this->hide($seller);
        $this->assertSame([$other->id], Product::visible()->pluck('id')->all());

        Cache::flush();
        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
        $this->assertStringNotContainsString('mouse-1', $sitemap);
        $this->assertStringNotContainsString('bad-shop', $sitemap);
        $this->assertStringContainsString('mouse-2', $sitemap);
    }

    public function test_seller_scope_and_cache_skip_hidden_seller(): void
    {
        [$seller] = $this->sellerWithProduct();
        $this->assertNotNull(Seller::getCachedByAlias('bad-shop'));

        app(SellerVisibilityService::class)->hide($this->administrator(), $seller, self::REASON);

        $this->assertNull(Seller::getCachedByAlias('bad-shop'));
        $this->assertFalse(Seller::getCachedAll()->contains('id', $seller->id));
    }

    // ------------------------------------------------------------------ состояния в кабинете

    public function test_product_of_hidden_seller_has_its_own_status_everywhere(): void
    {
        [$seller, $product] = $this->sellerWithProduct();
        $this->hide($seller);
        $product = $product->fresh();

        $cabinet = app(ProductCabinetStateService::class)->make($product);
        $this->assertSame('hidden_seller', $cabinet->status);
        $this->assertFalse($cabinet->visibleToBuyers);
        $this->assertSame('Магазин скрыт', $cabinet->label);
        $this->assertContains('seller_hidden', $cabinet->secondaryReasons);

        $story = app(ProductStatusStoryFactory::class)->make($product);
        $this->assertSame('seller_hidden', $story->key);
        $this->assertSame('danger', $story->tone);

        $view = app(ProductViewStateFactory::class)->make($product);
        $this->assertSame(ProductDisplayState::SellerHidden, $view->displayState);
        $this->assertFalse($view->isPublic);
        $this->assertFalse($view->canOpenPublicPage);
        $this->assertFalse($view->canPushUp);
        // Снять товар с публикации продавец по-прежнему может
        $this->assertTrue($view->canUnpublish);
        $this->assertTrue(app(ProductStateTransitionService::class)->canUnpublish($product));
    }

    public function test_vip_cannot_be_bought_for_hidden_shop(): void
    {
        [$seller, $product] = $this->sellerWithProduct();
        $this->hide($seller);

        $state = app(ProductVipService::class)->state($product->fresh());

        $this->assertFalse($state->eligible);
        $this->assertContains('seller_hidden', array_column($state->blockers, 'code'));
    }

    public function test_cabinet_state_of_hidden_shop_is_a_danger_alert(): void
    {
        [$seller] = $this->sellerWithProduct();
        $this->hide($seller);

        $state = app(SellerCabinetStateService::class)->make($seller->fresh());

        $this->assertSame('hidden', $state->status);
        $this->assertSame('danger', $state->alertType);
        $this->assertSame('Магазин скрыт', $state->label);
        // Причину объясняет красное уведомление; остальные замечания не дублируются
        $this->assertSame([], $state->blockers);
        $this->assertSame('edit_store', $state->primaryAction);
        $this->assertSame(0, $state->visibleProductsCount);
    }

    public function test_seller_event_journal_shows_the_reason_and_the_return(): void
    {
        [$seller] = $this->sellerWithProduct();
        $admin = $this->administrator();
        $service = app(SellerVisibilityService::class);
        $service->hide($admin, $seller, self::REASON);

        $events = collect(app(SellerEventTimelineService::class)->latestForSeller($seller->fresh()));
        $hidden = $events->firstWhere('title', 'Магазин скрыт администратором');
        $this->assertNotNull($hidden);
        $this->assertSame('Причина: '.self::REASON, $hidden['description']);
        $this->assertSame('danger', $hidden['tone']);

        $service->show($admin, $seller);

        $events = collect(app(SellerEventTimelineService::class)->latestForSeller($seller->fresh()));
        $this->assertSame('success', $events->firstWhere('title', 'Магазин снова показан покупателям')['tone']);
    }

    // ------------------------------------------------------------------ красное уведомление в кабинете

    public function test_seller_sees_red_notice_with_reason_on_cabinet_pages(): void
    {
        [$seller] = $this->sellerWithProduct();
        $this->hide($seller);

        foreach ([route('account.index'), route('account.store.products.index'), route('account.store.orders.index')] as $url) {
            $this->actingAs($seller->user)->get($url)
                ->assertOk()
                ->assertSee('seller-hidden-notice', false)
                ->assertSee('Ваш магазин скрыт администратором')
                ->assertSee(self::REASON);
        }
    }

    public function test_notice_escapes_the_reason_and_keeps_line_breaks(): void
    {
        [$seller] = $this->sellerWithProduct();
        $this->hide($seller, "<script>alert(1)</script>\nвторая строка");

        $this->actingAs($seller->user)->get(route('account.index'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('<br />', false)
            ->assertSee('вторая строка');
    }

    public function test_visible_shop_has_no_red_notice(): void
    {
        [$seller] = $this->sellerWithProduct();

        $this->actingAs($seller->user)->get(route('account.index'))
            ->assertOk()
            ->assertDontSee('seller-hidden-notice', false)
            ->assertDontSee('скрыт администратором');
    }

    public function test_notice_is_also_shown_in_the_new_cabinet_design(): void
    {
        config()->set('cabinet_design.default', 'legacy');
        SiteSetting::query()->updateOrCreate(['key' => config('cabinet_design.setting_key')], ['value' => 'new']);
        Cache::forget(config('cabinet_design.cache_key'));
        [$seller] = $this->sellerWithProduct();
        $this->hide($seller);

        try {
            $this->actingAs($seller->user)->get(route('account.index'))
                ->assertOk()
                ->assertSee('cabinet-new', false)
                ->assertSee('seller-hidden-notice', false)
                ->assertSee(self::REASON)
                // Ссылка «Открыть витрину» вела бы на 404
                ->assertDontSee('Открыть витрину');
        } finally {
            config()->set('cabinet_design.default', 'legacy');
            Cache::forget(config('cabinet_design.cache_key'));
        }
    }

    public function test_other_sellers_do_not_see_the_notice(): void
    {
        [$seller] = $this->sellerWithProduct();
        $this->hide($seller);
        $other = Seller::factory()->create();

        $this->actingAs($other->user)->get(route('account.index'))
            ->assertOk()
            ->assertDontSee('seller-hidden-notice', false);
    }

    public function test_hidden_shop_cannot_be_deleted_by_owner(): void
    {
        [$seller] = $this->sellerWithProduct();
        $this->hide($seller);

        $this->actingAs($seller->user)->get(route('account.store.delete'))
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('error');

        $this->actingAs($seller->user)
            ->delete(route('account.store.destroy'), ['password' => 'password', 'confirmation' => 'удалить'])
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('error');

        $this->assertNotNull(Seller::find($seller->id));
    }

    public function test_qr_code_is_unavailable_while_shop_is_hidden(): void
    {
        [$seller] = $this->sellerWithProduct();

        $this->actingAs($seller->user)->get(route('account.store.qr'))->assertOk();

        $this->hide($seller);
        $owner = User::find($seller->user_id);

        $this->actingAs($owner)->get(route('account.store.qr'))
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('error');
        $this->actingAs($owner)->get(route('account.store.qr.download', 'poster'))
            ->assertRedirect(route('account.index'));
    }
}
