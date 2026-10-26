<?php

namespace Tests\Feature;

use App\Services\DesignManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Основное меню шапки (классическая тема): «Мои заказы» стоит последним пунктом и ведёт в заказы покупателя. */
class HeaderMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_menu_has_my_orders_item_after_sale(): void
    {
        app(DesignManager::class)->setActive('legacy');

        $html = $this->get(route('home'))->assertOk()->getContent();

        preg_match('/<nav class="main-menu".*?<\/nav>/s', $html, $menu);
        $this->assertNotEmpty($menu, 'Не найдено основное меню шапки.');

        $this->assertStringContainsString(
            '<li class="level1"><a href="'.route('orders.index').'"><img src="'.asset('img/icon-orders.svg').'" alt="">Мои заказы</a></li>',
            $menu[0],
        );
        $this->assertGreaterThan(strpos($menu[0], 'Распродажи сейчас'), strpos($menu[0], 'Мои заказы'));
        $this->assertFileExists(public_path('img/icon-orders.svg'));
    }

    private function authBlock(string $html): string
    {
        preg_match('/<div class="header-auth hidden-xs hidden-sm">.*?<\/div>/s', $html, $block);
        $this->assertNotEmpty($block, 'Не найден блок входа и регистрации в шапке.');

        return $block[0];
    }

    public function test_guest_sees_seller_login_and_registration_buttons_instead_of_icons(): void
    {
        app(DesignManager::class)->setActive('legacy');

        $html = $this->get(route('home'))->assertOk()->getContent();
        $block = $this->authBlock($html);

        $this->assertStringContainsString('href="'.route('login').'">Войти</a>', $block);
        $this->assertStringContainsString('href="'.route('register').'"', $block);
        $this->assertStringContainsString('Стать продавцом', $block);
        $this->assertStringContainsString('Регистрация', $block); // короткая подпись для окон уже 1200 px
        $this->assertStringNotContainsString('Мой кабинет', $block);

        // иконка кабинета убрана, а «Войти продавцу» из верхней полосы больше не дублирует кнопку
        $this->assertStringNotContainsString('icon-user.png', $html);
        $this->assertStringNotContainsString('<span>Войти продавцу</span>', $html);

        // на мобильных избранное и заказы остаются иконками (на десктопе их скрывает CSS)
        $this->assertStringContainsString('class="header-orders-link"', $html);
        $this->assertStringContainsString('icon-heart.png', $html);
    }

    public function test_gray_top_strip_is_gone_but_seller_help_stays_reachable(): void
    {
        config(['regionality.enabled' => true]);
        \App\Models\Settlement::factory()->for(\App\Models\Region::factory()->create())->create();
        app(DesignManager::class)->setActive('legacy');

        $html = $this->get(route('home'))->assertOk()->getContent();

        // полосы с выбором региона и «Помощью продавцам» в шапке больше нет
        $this->assertStringNotContainsString('class="topbar"', $html);
        $this->assertStringNotContainsString('regional-selector--topbar', $html);
        $this->assertStringNotContainsString('header-mobile-region', $html);
        $this->assertStringNotContainsString('>Помощь продавцам<', $html);

        // страница для продавцов по-прежнему доступна из подвала, а выбор региона стоит в шапке на компьютере;
        // отдельной строки под шапкой на телефоне и выбора в бургер-меню нет
        $this->assertStringContainsString('<a href="'.route('seller.help').'">Как продавать</a>', $html);
        $this->assertStringContainsString('region-switcher--header', $html);
        $this->assertStringNotContainsString('header-region-bar', $html);
        $this->assertStringNotContainsString('regional-selector--push-menu', $html);
        $this->assertStringContainsString('<header id="header" class="header-v1">', $html);
    }

    public function test_header_search_keeps_the_query_when_the_field_gets_focus(): void
    {
        app(DesignManager::class)->setActive('legacy');

        $html = $this->get(route('search', ['q' => 'кир', 'type' => 'product']))->assertOk()->getContent();

        preg_match('/<input type="text" name="q" class="form-control"[^>]*>/', $html, $input);
        $this->assertNotEmpty($input, 'Не найдено поле поиска в шапке.');

        $this->assertStringContainsString('value="кир"', $input[0]);
        // старые встроенные обработчики стирали запрос при клике и возвращали его при уходе из поля
        $this->assertStringNotContainsString('onfocus', $input[0]);
        $this->assertStringNotContainsString('onblur', $input[0]);
        $this->assertStringNotContainsString('this.defaultValue', $html);

        $css = file_get_contents(public_path('css/marketplace-design.css'));
        $this->assertStringContainsString('.header-v1 .header-search form:focus-within input[name="q"]', $css);
    }

    public function test_logged_in_seller_sees_single_cabinet_button(): void
    {
        app(DesignManager::class)->setActive('legacy');

        $block = $this->authBlock(
            $this->actingAs(\App\Models\User::factory()->create())->get(route('home'))->assertOk()->getContent()
        );

        $this->assertStringContainsString('href="'.route('account.index').'"', $block);
        $this->assertStringContainsString('Мой кабинет', $block);
        $this->assertStringNotContainsString(route('register'), $block);
        $this->assertStringNotContainsString('>Войти</a>', $block);
    }

    public function test_header_styles_exist_for_buttons_and_hide_icons_on_desktop(): void
    {
        $css = file_get_contents(public_path('css/marketplace-design.css'));

        $this->assertStringContainsString('.header-auth__btn--primary', $css);
        $this->assertStringContainsString('.header-sub .header-sub-element.row > a:not(.header-auth__btn) { display: none; }', $css);
        // колонка справа больше не зажата в 172 px под три иконки
        $this->assertStringNotContainsString('flex: 0 0 172px', $css);
    }
}
