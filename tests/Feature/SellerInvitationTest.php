<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Приглашение стать продавцом: блок на главной сразу после первого блока (на телефоне и планшете; на компьютере его нет,
 * там кнопка в шапке) и кнопки вверху бургер-меню.
 */
class SellerInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_become_a_seller_block_right_after_the_hero_on_small_screens(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Хочешь стать продавцом?')
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('href="'.route('seller.help').'"', false)
            ->assertSee('Уже продаёте?')
            // классы Bootstrap hidden-md hidden-lg прячут блок от 1025 px (так шаблон переопределил Bootstrap): два широких блока подряд на компьютере выглядели как два баннера
            ->assertSeeInOrder(['class="home-hero"', 'class="home-become-seller hidden-md hidden-lg"'], false);
    }

    public function test_user_without_a_store_is_offered_to_open_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('Хочешь стать продавцом?')
            ->assertSee('Открыть магазин')
            ->assertSee('href="'.route('account.store.create').'"', false)
            ->assertDontSee('Уже продаёте?');
    }

    public function test_seller_with_a_store_does_not_see_the_block(): void
    {
        $user = User::factory()->create();
        Seller::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertDontSee('Хочешь стать продавцом?');
    }

    public function test_shops_page_header_is_compact_and_offers_seller_buttons(): void
    {
        $this->get(route('shops'))
            ->assertOk()
            ->assertSee('class="shops-hero__actions"', false)
            ->assertSee('Продаёте товары?')
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('Войти в кабинет')
            // прежние подпись, описание и карточка с числом магазинов убраны
            ->assertDontSee('Выбирай свой город')
            ->assertDontSee('Смотрите ассортимент, адреса и контакты');

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('shops'))
            ->assertSee('Открыть магазин')
            ->assertSee('href="'.route('account.store.create').'"', false)
            ->assertDontSee('Войти в кабинет');

        Seller::factory()->create(['user_id' => $user->id]);
        $user = User::find($user->id); // связь «магазин» у прежнего объекта уже закеширована как пустая
        $this->actingAs($user)->get(route('shops'))
            ->assertSee('<a class="shops-hero__btn shops-hero__btn--primary" href="'.route('account.index').'">Мой кабинет</a>', false)
            ->assertDontSee('Продаёте товары?');
    }

    public function test_burger_menu_starts_with_seller_buttons_and_has_no_search_or_title(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<a class="drawer__cta" href="'.route('register').'">Стать продавцом</a>', $html);
        $this->assertStringContainsString('Уже продаёте? <a href="'.route('login').'">Войти</a>', $html);

        // кнопка стоит раньше категорий, а заголовка, поиска, выбора города и старого блока внизу больше нет
        $this->assertLessThan(strpos($html, 'class="drawer__label"'), strpos($html, 'drawer__cta'));
        foreach (['Меню маркетплейса', 'mobile-menu-heading', 'mobile-menu-search', 'id="searchform"', 'mobile-seller-entry', 'mobile-menu-region', 'mobile-menu-seller'] as $removed) {
            $this->assertStringNotContainsString($removed, $html, $removed);
        }
    }

    public function test_logged_in_user_gets_a_cabinet_button_instead_of_the_seller_invitation_in_the_burger_menu(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('<a class="drawer__cta" href="'.route('account.index').'">Мой кабинет</a>', false)
            ->assertDontSee('drawer__login', false);
    }
}
