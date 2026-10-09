<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_dynamic_marketplace_sections(): void
    {
        $category = Category::factory()->create(['name' => 'Категория главной']);
        $emptyCategory = Category::factory()->create(['name' => 'Пустая категория главной']);
        $seller = Seller::factory()->create(['name' => 'Магазин главной']);


        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => 'Товар главной',
            'price' => 1000,
            'lowprice' => 800,
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Где купить? Смотри на BIMM.')
            ->assertSee('Маркетплейс Приднестровья')
            ->assertSee('BIMM собирает товары продавцов из Приднестровья в одном месте')
            ->assertSee('Товары, которые уже есть на BIMM')
            ->assertSee('Новые товары продавцов')
            ->assertSee('Товары со скидкой')
            ->assertSee('Продавцы на BIMM')
            ->assertSee('Не ищи по чатам. Смотри:')
            ->assertSee('Всё, что продаётся рядом, должно быть видно')
            ->assertSee('Как BIMM помогает купить')
            ->assertSee($category->name)
            ->assertSee(route('catalog.show', ['alias' => $category->alias]), false)
            ->assertDontSee('class="tags"', false)
            ->assertDontSee('href="'.route('search').'?q=', false)
            ->assertDontSee($emptyCategory->name)
            ->assertSee($seller->name)
            ->assertSee($product->name)
            ->assertSee('class="home-hero__panel home-hero__panel--search"', false)
            ->assertSee('class="site-footer"', false)
            ->assertSee(route('catalog.index'), false)
            ->assertSee(route('shops'), false)
            ->assertSee(route('catalog.sale'), false)
            ->assertSee(route('favorites'), false)
            ->assertSee(route('search'), false)
            ->assertSee('title="Главная"', false)
            ->assertSee('title="Поиск"', false)
            ->assertSee('icon-heart.png', false)
            ->assertSee('class="hidden-md hidden-lg icon-pushmenu js-push-menu"', false)
            ->assertSee('class="header-orders-link"', false)
            ->assertSee(route('orders.index'), false)
            ->assertDontSee('icon-cart.png', false)
            ->assertDontSee('class="cart"', false)
            ->assertDontSee('dropdown-cart', false)
            ->assertSee('class="mobile-menu-region"', false)
            ->assertSee('regional-selector--push-menu', false)
            ->assertSee('id="mobile-menu-query"', false)
            ->assertSee('class="mobile-menu-categories-head"', false)
            ->assertSee('class="mobile-menu-links"', false)
            ->assertSee('class="mobile-seller-entry"', false)
            ->assertSee('Категории товаров')
            ->assertSee('Товары, магазины и вход для продавцов')
            ->assertSee('Войти продавцу')
            ->assertSee('Стать продавцом')
            ->assertSee('Оплата проходит напрямую продавцу')
            ->assertSee('BIMM собирает товары продавцов из Приднестровья в одном месте')
            ->assertSee('Не ищите по чатам: смотрите товары и продавцов на BIMM.')
            ->assertSee('Всё, что продаётся рядом, должно быть видно.')
            ->assertDontSee('Contact@yourcompany.com')
            ->assertDontSee('в Instagram')
            ->assertDontSee('Открыть каталог')
            ->assertDontSee('Смотреть магазины')
            ->assertDontSee('Скидки и акции')
            ->assertDontSee('Power to the pro')
            ->assertDontSee('Shop now');

        $css = file_get_contents(public_path('css/marketplace-design.css'));
        $this->assertStringContainsString('.menu-home5.pushmenu-open', $css);
        $this->assertStringContainsString('body.pushmenu-push-toleft', $css);
        $this->assertStringContainsString('left: 0;', $css);
        $this->assertStringContainsString('box-shadow: none;', $css);
        $this->assertStringContainsString('box-shadow: 18px 0 45px rgba(30, 30, 40, .16);', $css);
        $this->assertStringContainsString('transform: translate3d(-100%, 0, 0);', $css);
        $this->assertStringContainsString('transform: translate3d(0, 0, 0) !important;', $css);
        $this->assertStringContainsString('transform: none !important;', $css);
        $this->assertStringContainsString('.pushmenu.menu-home5 .screen-reader-text', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr) 86px;', $css);
        $this->assertStringContainsString('.header-sub .header-sub-element.row > a', $css);
        $this->assertStringContainsString('gap: 18px;', $css);
        $this->assertStringContainsString('width: 32px;', $css);
        $this->assertStringContainsString('height: 32px;', $css);
        $this->assertStringContainsString('.header-sub .header-sub-element.row { gap: 12px; }', $css);
        $this->assertStringContainsString('.header-sub .header-sub-element.row > a { width: 30px; height: 30px; }', $css);
    }

    public function test_public_header_logo_uses_stable_desktop_layout(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('aria-label="BIMM — на главную"', false)
            ->assertSee('alt="BIMM — товары Приднестровья"', false)
            ->assertSee('class="img-responsive"', false)
            ->assertDontSee('class="img-reponsive"', false);

        $css = file_get_contents(public_path('css/marketplace-design.css'));

        $this->assertStringContainsString('.header-v1 .header-logo', $css);
        $this->assertStringContainsString('margin-top: 0 !important;', $css);
        $this->assertStringContainsString('.header-v1 .header-center .row.flex', $css);
        $this->assertStringContainsString('flex-wrap: nowrap;', $css);
        $this->assertStringContainsString('display: flex !important;', $css);
        $this->assertStringContainsString('float: none;', $css);
        $this->assertStringContainsString('visibility: visible !important;', $css);
        $this->assertStringContainsString('width: 197px;', $css);
    }
}
