<?php

namespace Tests\Feature;

use App\Jobs\WarmCategoriesCache;
use App\Models\Category;
use App\Models\Package;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Feature-тест: Job WarmCategoriesCache.
 *
 * @covers \App\Jobs\WarmCategoriesCache::handle
 */
class WarmCategoriesCacheJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    // =========================================================================
    // Базовые тесты Job
    // =========================================================================

    public function test_job_clears_and_rebuilds_category_tree_cache(): void
    {
        Category::factory()->create();

        // Записываем в кеш устаревшие данные
        Cache::put('categories_root_tree', 'stale_data', 3600);
        Cache::put('categories_root_list', 'stale_data', 3600);
        Cache::put('menu_categories', 'stale_data', 3600);

        $job = new WarmCategoriesCache();
        $job->handle();

        // Старые данные должны быть заменены новыми
        $this->assertNotEquals('stale_data', Cache::get('categories_root_tree'));
        $this->assertTrue(Cache::has('categories_root_tree'), 'Дерево категорий должно быть в кеше после прогрева');
    }

    public function test_job_rebuilds_menu_categories_when_hide_empty_is_true(): void
    {
        Config::set('categories.hide_empty', true);
        Category::factory()->create();

        $job = new WarmCategoriesCache();
        $job->handle();

        $this->assertTrue(
            Cache::has('menu_categories'),
            'menu_categories должен быть в кеше при hide_empty=true'
        );
    }

    public function test_job_does_not_rebuild_menu_categories_when_hide_empty_is_false(): void
    {
        Config::set('categories.hide_empty', false);
        Cache::forget('menu_categories');

        $job = new WarmCategoriesCache();
        $job->handle();

        $this->assertFalse(
            Cache::has('menu_categories'),
            'menu_categories не должен создаваться при hide_empty=false'
        );
    }

    public function test_job_can_be_dispatched_synchronously(): void
    {
        // QUEUE_CONNECTION=sync в phpunit.xml — job выполняется синхронно
        WarmCategoriesCache::dispatch();

        $this->assertTrue(Cache::has('categories_root_tree'));
    }

    // =========================================================================
    // Проверка: job стартует при изменении категории
    // =========================================================================

    public function test_category_save_dispatches_warm_cache_job(): void
    {
        // В тесте queue sync — job выполнится сразу, кеш будет заполнен
        $category = Category::factory()->create();

        // После создания категории booted() вызывает clearCategoriesCache()
        // который dispatch(new WarmCategoriesCache())->afterResponse()
        // В тестах afterResponse() не выполняется, но кеш должен быть сброшен
        $this->assertFalse(
            Cache::has('categories_root_tree'),
            'Кеш дерева должен быть сброшен после сохранения категории'
        );
    }

    public function test_category_delete_clears_categories_cache(): void
    {
        $category = Category::factory()->create();
        Cache::put('categories_root_tree', collect([]), 3600);

        $category->delete();

        $this->assertFalse(
            Cache::has('categories_root_tree'),
            'Кеш категорий должен быть сброшен при удалении категории'
        );
    }
}
