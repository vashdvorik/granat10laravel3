<?php

namespace Tests\Feature;

use Tests\TestCase;

class SellerHelpPageTest extends TestCase
{
    public function test_seller_help_page_is_available(): void
    {
        $this->get(route('seller.help'))
            ->assertOk()
            ->assertSee('Ваш товар уже ищут')
            ->assertSee('Как подключиться')
            ->assertSee('Что важно заполнить')
            ->assertSee('Как работает заказ')
            ->assertSee('class="seller-help-hero__panel"', false)
            ->assertSee('class="market-btn market-btn--primary"', false)
            ->assertSee('.seller-help-cta .market-btn--primary{background:linear-gradient(90deg,#b965ed,#4ed9ee);color:#fff!important;', false)
            ->assertDontSee('Multi-Purpose WooCommerce Theme');
    }

    public function test_legacy_gobuyer_page_still_opens_seller_help(): void
    {
        $this->get(route('gobuyer'))
            ->assertOk()
            ->assertSee('Помощь продавцам')
            ->assertSee('Ваш товар уже ищут');
    }

    public function test_layout_links_to_seller_help_route(): void
    {
        $this->get(route('seller.help'))
            ->assertOk()
            ->assertSee(route('seller.help'), false)
            ->assertDontSee('href="'.route('gobuyer').'"', false);
    }
}
