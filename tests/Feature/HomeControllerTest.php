<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
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
        $package = Package::factory()->withLimits(50, 10)->create();

        SellerPackage::factory()->create([
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => null,
        ]);

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
            ->assertSee('Популярные категории')
            ->assertSee('Новые товары')
            ->assertSee('Выгодные предложения')
            ->assertSee('Магазины рядом')
            ->assertSee('Часто ищут')
            ->assertSee($category->name)
            ->assertSee(route('catalog.show', ['alias' => $category->alias]), false)
            ->assertDontSee('class="tags"', false)
            ->assertDontSee('/search?q=', false)
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
            ->assertSee('Наличие, цену и доставку уточняйте у продавца перед покупкой.')
            ->assertDontSee('Contact@yourcompany.com')
            ->assertDontSee('Гранат в Instagram')
            ->assertDontSee('Открыть каталог')
            ->assertDontSee('Смотреть магазины')
            ->assertDontSee('Скидки и акции')
            ->assertDontSee('Power to the pro')
            ->assertDontSee('Shop now');

        $css = file_get_contents(public_path('css/marketplace-design.css'));
        $this->assertStringContainsString('.menu-home5.pushmenu-open', $css);
        $this->assertStringContainsString('body.pushmenu-push-toleft', $css);
        $this->assertStringContainsString('left: 0;', $css);
        $this->assertStringContainsString('transform: translate3d(-100%, 0, 0);', $css);
        $this->assertStringContainsString('transform: translate3d(0, 0, 0) !important;', $css);
        $this->assertStringContainsString('transform: none !important;', $css);
        $this->assertStringContainsString('.pushmenu.menu-home5 .screen-reader-text', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr) 86px;', $css);
    }
}
