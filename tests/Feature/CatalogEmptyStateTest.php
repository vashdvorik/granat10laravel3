<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Каталог или категория без товаров (например, выбран город, где продавцов ещё нет): вместо схлопнувшегося меню слева
 * и одной строки «Нет товаров» показывается карточка с понятным заголовком и действием.
 */
class CatalogEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('regionality.enabled', true);
        config()->set('regionality.urls_enabled', true);
        Cache::flush();
    }

    public function test_regional_catalog_without_products_shows_the_empty_card_instead_of_an_empty_menu(): void
    {
        Settlement::factory()->create(['name' => 'Тирасполь', 'slug' => 'tiraspol-empty']);

        $this->get(route('regional.catalog.index', ['location' => 'tiraspol-empty']))
            ->assertOk()
            ->assertSee('class="catalog-empty"', false)
            ->assertSee('В Тирасполе пока нет товаров')
            ->assertSee('Показать все регионы')
            ->assertDontSee('id="filter-sidebar"', false)
            ->assertDontSee('Показано 0–0')
            ->assertDontSee('Нет товаров в этой категории.')
            // плашка города заменена карточкой с тем же действием
            ->assertDontSee('region-notice', false);
    }

    public function test_regional_category_without_products_names_the_category_and_offers_a_way_back(): void
    {
        Settlement::factory()->create(['name' => 'Тирасполь', 'slug' => 'tiraspol-empty']);
        Category::factory()->create(['parent_id' => null, 'name' => 'Пустая категория', 'alias' => 'empty-cat']);

        $this->get(route('regional.catalog.show', ['location' => 'tiraspol-empty', 'alias' => 'empty-cat']))
            ->assertOk()
            ->assertSee('class="catalog-empty"', false)
            ->assertSee('В Тирасполе в категории «Пустая категория» пока нет товаров')
            ->assertSee('Показать все регионы')
            ->assertSee('В каталог')
            ->assertDontSee('id="filter-sidebar"', false);
    }

    public function test_empty_catalog_without_a_city_has_no_reset_button(): void
    {
        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('class="catalog-empty"', false)
            ->assertSee('Товаров пока нет')
            ->assertDontSee('Показать все регионы');
    }

    public function test_catalog_with_products_keeps_the_menu_and_the_region_notice(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Тирасполь', 'slug' => 'tiraspol-empty']);
        $seller = Seller::factory()->for($settlement)->create();
        $category = Category::factory()->create(['parent_id' => null, 'alias' => 'full-cat']);
        Product::factory()->count(2)->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_active' => true]);

        $this->get(route('regional.catalog.index', ['location' => 'tiraspol-empty']))
            ->assertOk()
            ->assertSee('id="filter-sidebar"', false)
            ->assertSee('region-notice', false)
            ->assertDontSee('class="catalog-empty"', false);
    }
}
