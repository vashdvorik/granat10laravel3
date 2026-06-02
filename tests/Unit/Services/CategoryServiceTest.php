<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoryService $categoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryService = new CategoryService;
    }

    public function test_can_get_category_tree(): void
    {
        Category::factory()->count(3)->create(['parent_id' => null]);

        $tree = $this->categoryService->getCategoryTree();

        $this->assertNotEmpty($tree);
        $this->assertGreaterThanOrEqual(3, $tree->count());
    }

    public function test_can_get_root_categories(): void
    {
        Category::factory()->count(5)->create(['parent_id' => null]);

        $roots = $this->categoryService->getRootCategories();

        $this->assertEquals(5, $roots->count());
    }

    public function test_can_get_category_by_id(): void
    {
        $category = Category::factory()->create(['name' => 'Test Category']);

        $found = $this->categoryService->getCategoryById($category->id);

        $this->assertNotNull($found);
        $this->assertEquals('Test Category', $found->name);
    }

    public function test_returns_null_for_non_existent_category(): void
    {
        $found = $this->categoryService->getCategoryById(99999);

        $this->assertNull($found);
    }

    public function test_can_get_breadcrumbs(): void
    {
        $parent = Category::factory()->create(['name' => 'Parent']);
        $child = Category::factory()->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $breadcrumbs = $this->categoryService->getBreadcrumbs($child);

        $this->assertEquals(2, $breadcrumbs->count());
        $this->assertEquals('Parent', $breadcrumbs->first()->name);
        $this->assertEquals('Child', $breadcrumbs->last()->name);
    }

    public function test_can_get_children(): void
    {
        $parent = Category::factory()->create();
        Category::factory()->count(3)->create(['parent_id' => $parent->id, 'is_active' => true]);

        $children = $this->categoryService->getChildren($parent);

        $this->assertEquals(3, $children->count());
    }

    public function test_clear_cache_removes_all_category_caches(): void
    {
        $category = Category::factory()->create();

        // Устанавливаем кэши
        Cache::put("category.{$category->id}", $category, 60);
        Cache::put("category.{$category->id}.breadcrumbs", [], 60);

        // Очищаем
        $this->categoryService->clearCache($category);

        // Проверяем что кэш очищен
        $this->assertFalse(Cache::has("category.{$category->id}"));
        $this->assertFalse(Cache::has("category.{$category->id}.breadcrumbs"));
    }

    public function test_category_service_uses_cache(): void
    {
        $category = Category::factory()->create();

        // Первый вызов - кэширование
        $first = $this->categoryService->getCategoryById($category->id);

        // Второй вызов - из кэша
        $second = $this->categoryService->getCategoryById($category->id);

        $this->assertEquals($first->id, $second->id);
        $this->assertTrue(Cache::has("category.{$category->id}"));
    }
}
