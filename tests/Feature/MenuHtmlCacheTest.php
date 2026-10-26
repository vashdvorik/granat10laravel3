<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Settlement;
use App\Services\CatalogCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Полное дерево категорий (около 230 КБ HTML) не приходит с каждой страницей: на странице только верхний уровень,
 * а всё дерево боковое меню подгружает при открытии (MenuController). Готовый HTML дерева лежит в кеше
 * отдельно для каждого города (в ссылках стоит адрес города) и сбрасывается вместе с данными меню.
 */
class MenuHtmlCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('regionality.enabled', true);
        config()->set('regionality.urls_enabled', true);
        config()->set('categories.hide_empty', false);
        Cache::flush();
    }

    private function menuCategory(string $name, string $alias, ?Category $parent = null): Category
    {
        return Category::factory()->create([
            'parent_id' => $parent?->id,
            'name' => $name,
            'alias' => $alias,
            'is_active' => true,
        ]);
    }

    public function test_page_has_only_top_level_categories_and_points_to_the_full_tree(): void
    {
        $root = $this->menuCategory('Верхняя категория', 'menu-root');
        $this->menuCategory('Вложенная категория', 'menu-child', $root);

        $html = $this->get(route('shops'))->assertOk()->getContent();

        $this->assertSame(1, preg_match('#<ul class="nav-home5 js-menubar" data-menu-url="([^"]+)">(.*?)</ul>#s', $html, $menu));
        $this->assertSame(route('menu.categories'), $menu[1]);
        $this->assertStringContainsString('Верхняя категория', $menu[2]);
        $this->assertStringNotContainsString('Вложенная категория', $html);
        $this->assertStringNotContainsString('icon-sub-menu', $menu[2]);
        $this->assertStringContainsString('js/drawer-menu.js', $html);
    }

    public function test_endpoint_returns_the_full_tree_with_expand_arrows(): void
    {
        $root = $this->menuCategory('Верхняя категория', 'menu-root');
        $child = $this->menuCategory('Вложенная категория', 'menu-child', $root);
        $this->menuCategory('Третий уровень', 'menu-grandchild', $child);

        $response = $this->get(route('menu.categories'))->assertOk();

        $response->assertSee('Верхняя категория')
            ->assertSee('Вложенная категория')
            ->assertSee('Третий уровень')
            ->assertSee('icon-sub-menu', false)
            ->assertSee('href="'.route('catalog.show', ['alias' => 'menu-child']).'"', false);
        $this->assertStringContainsString('max-age=300', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
    }

    public function test_menu_html_is_cached_after_the_first_request(): void
    {
        $this->menuCategory('Меню категория', 'menu-cache');
        $key = app(CatalogCacheService::class)->regionalKey(CatalogCacheService::MENU_HTML_KEY);

        $this->assertFalse(Cache::has($key));

        $this->get(route('menu.categories'))->assertOk()->assertSee('Меню категория');

        $this->assertTrue(Cache::has($key));
        $this->assertStringContainsString('Меню категория', Cache::get($key));
    }

    public function test_menu_html_is_dropped_when_a_category_changes(): void
    {
        $category = $this->menuCategory('Старое название', 'menu-rename');
        $key = app(CatalogCacheService::class)->regionalKey(CatalogCacheService::MENU_HTML_KEY);

        $this->get(route('menu.categories'))->assertOk();
        $this->assertStringContainsString('Старое название', Cache::get($key));

        // дерево в одном процессе запоминает AppServiceProvider, поэтому проверяем сам сброс: на сайте следующий запрос
        // идёт в новом процессе и соберёт меню заново
        $category->update(['name' => 'Новое название']);

        $this->assertFalse(Cache::has($key));
    }

    public function test_menu_html_is_cached_separately_for_each_city(): void
    {
        $this->menuCategory('Меню города', 'menu-city');
        Settlement::factory()->create(['slug' => 'menu-city-town']);

        $common = $this->get(route('menu.categories'))->assertOk()->getContent();
        $regional = $this->get(route('regional.menu.categories', ['location' => 'menu-city-town']))->assertOk()->getContent();

        $commonLink = route('catalog.show', ['alias' => 'menu-city']);
        $regionalLink = route('regional.catalog.show', ['location' => 'menu-city-town', 'alias' => 'menu-city']);

        $this->assertStringContainsString('href="'.$commonLink.'"', $common);
        $this->assertStringNotContainsString('href="'.$regionalLink.'"', $common);
        $this->assertStringContainsString('href="'.$regionalLink.'"', $regional);
        $this->assertStringNotContainsString('href="'.$commonLink.'"', $regional);
    }

    public function test_page_in_a_city_points_to_the_menu_of_that_city(): void
    {
        $this->menuCategory('Меню города', 'menu-city');
        Settlement::factory()->create(['slug' => 'menu-city-town']);

        $this->get(route('regional.home', ['location' => 'menu-city-town']))
            ->assertOk()
            ->assertSee('data-menu-url="'.route('regional.menu.categories', ['location' => 'menu-city-town']).'"', false);
    }

    public function test_forgetting_menu_data_also_drops_the_menu_html_of_every_city(): void
    {
        $city = Settlement::factory()->create();
        $cache = app(CatalogCacheService::class);

        Cache::put(CatalogCacheService::MENU_HTML_KEY, 'html');
        Cache::put(CatalogCacheService::MENU_HTML_KEY.':settlement:'.$city->id, 'html');

        $cache->forgetMenuCategories();

        $this->assertFalse(Cache::has(CatalogCacheService::MENU_HTML_KEY));
        $this->assertFalse(Cache::has(CatalogCacheService::MENU_HTML_KEY.':settlement:'.$city->id));
    }
}
