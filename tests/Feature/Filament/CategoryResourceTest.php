<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\CategoryTree;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CategoryTreeService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
        Cache::flush();
    }

    public function test_administrator_can_open_category_resource_and_tree(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator)->get('/control/categories')->assertOk();
        $this->actingAs($administrator)->get('/control/category-tree')->assertOk();
    }

    public function test_regular_user_cannot_open_category_resource_or_tree(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/control/categories')->assertForbidden();
        $this->actingAs($user)->get('/control/category-tree')->assertForbidden();
    }

    public function test_administrator_can_create_root_and_child_categories(): void
    {
        $administrator = $this->createAdministrator();
        $this->actingAs($administrator);

        Livewire::test(CreateCategory::class)
            ->fillForm($this->categoryData('Корневая категория', 'root-category'))
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $root = Category::query()->where('alias', 'root-category')->firstOrFail();

        Livewire::test(CreateCategory::class)
            ->fillForm($this->categoryData(
                'Дочерняя категория',
                'child-category',
                $root->id,
                false,
            ))
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $child = Category::query()->where('alias', 'child-category')->firstOrFail();

        $root->refresh();

        $this->assertTrue($root->isRoot());
        $this->assertSame($root->id, $child->parent_id);
        $this->assertTrue($child->isDescendantOf($root));
        $this->assertFalse($child->is_active);
        $this->assertTrue(Cache::has('categories_root_tree'));
    }

    public function test_edit_moves_category_with_nested_set_methods(): void
    {
        $administrator = $this->createAdministrator();
        $service = app(CategoryTreeService::class);
        $firstRoot = $service->create($administrator, $this->categoryData('Первый корень', 'first-root'));
        $secondRoot = $service->create($administrator, $this->categoryData('Второй корень', 'second-root'));
        $child = $service->create(
            $administrator,
            $this->categoryData('Дочерняя', 'child', $firstRoot->id),
        );

        $this->actingAs($administrator);

        Livewire::test(EditCategory::class, ['record' => $child->getRouteKey()])
            ->fillForm($this->categoryData(
                'Перемещённая категория',
                'child',
                $secondRoot->id,
            ))
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $child->refresh();

        $this->assertSame($secondRoot->id, $child->parent_id);
        $this->assertTrue($child->isDescendantOf($secondRoot->fresh()));
        $this->assertFalse($child->isDescendantOf($firstRoot->fresh()));
    }

    public function test_service_rejects_self_parent_and_descendant_parent(): void
    {
        $administrator = $this->createAdministrator();
        $service = app(CategoryTreeService::class);
        $root = $service->create($administrator, $this->categoryData('Корень', 'root'));
        $child = $service->create(
            $administrator,
            $this->categoryData('Потомок', 'child', $root->id),
        );

        try {
            $service->update(
                $administrator,
                $root,
                $this->categoryData('Корень', 'root', $root->id),
            );
            $this->fail('ValidationException was not thrown for self parent.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('parent_id', $exception->errors());
        }

        try {
            $service->update(
                $administrator,
                $root,
                $this->categoryData('Корень', 'root', $child->id),
            );
            $this->fail('ValidationException was not thrown for descendant parent.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('parent_id', $exception->errors());
        }

        $this->assertTrue($root->fresh()->isRoot());
        $this->assertSame($root->id, $child->fresh()->parent_id);
    }

    public function test_delete_action_blocks_categories_with_children_or_products(): void
    {
        $administrator = $this->createAdministrator();
        $service = app(CategoryTreeService::class);
        $parent = $service->create($administrator, $this->categoryData('Родитель', 'parent'));
        $service->create(
            $administrator,
            $this->categoryData('Ребёнок', 'child', $parent->id),
        );
        $productCategory = $service->create(
            $administrator,
            $this->categoryData('С товарами', 'with-products'),
        );
        Product::factory()->create(['category_id' => $productCategory->id]);

        $this->actingAs($administrator);

        Livewire::test(ListCategories::class)
            ->callTableAction('deleteCategory', $parent)
            ->assertTableActionHalted('deleteCategory');

        Livewire::test(ListCategories::class)
            ->callTableAction('deleteCategory', $productCategory)
            ->assertTableActionHalted('deleteCategory');

        $this->assertDatabaseHas('categories', ['id' => $parent->id]);
        $this->assertDatabaseHas('categories', ['id' => $productCategory->id]);
    }

    public function test_empty_leaf_category_can_be_deleted(): void
    {
        $administrator = $this->createAdministrator();
        $category = app(CategoryTreeService::class)->create(
            $administrator,
            $this->categoryData('Пустая', 'empty'),
        );

        $this->actingAs($administrator);

        Livewire::test(ListCategories::class)
            ->callTableAction('deleteCategory', $category)
            ->assertNotified();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertTrue(Cache::has('categories_root_tree'));
    }

    public function test_tree_page_displays_hierarchy_status_counts_and_edit_links(): void
    {
        $administrator = $this->createAdministrator();
        $service = app(CategoryTreeService::class);
        $root = $service->create($administrator, $this->categoryData('Каталог', 'catalog'));
        $child = $service->create(
            $administrator,
            $this->categoryData('Скрытый раздел', 'hidden', $root->id, false),
        );
        Product::factory()->create(['category_id' => $child->id]);

        $this->actingAs($administrator);

        Livewire::test(CategoryTree::class)
            ->assertSee('Дерево категорий')
            ->assertSee('Каталог')
            ->assertSee('Скрытый раздел')
            ->assertSee('1 товаров')
            ->assertSee('Скрыта')
            ->assertSee(CategoryResource::getUrl('edit', ['record' => $child]));
    }

    public function test_regular_user_cannot_use_category_tree_service(): void
    {
        $this->expectException(AuthorizationException::class);

        app(CategoryTreeService::class)->create(
            User::factory()->create(),
            $this->categoryData('Запрещённая', 'forbidden'),
        );
    }

    private function createAdministrator(): User
    {
        $role = Role::query()->create([
            'name' => 'admin-'.uniqid(),
            'display_name' => 'Администратор',
        ]);
        $permission = Permission::query()->create([
            'key' => 'browse_admin',
            'table_name' => null,
        ]);
        $role->permissions()->attach($permission);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function categoryData(
        string $name,
        string $alias,
        ?int $parentId = null,
        bool $active = true
    ): array {
        return [
            'name' => $name,
            'alias' => $alias,
            'number' => 1,
            'parent_id' => $parentId,
            'is_active' => $active,
            'icon' => null,
            'icon_color' => '#696969',
            'bg_color' => '#e6e6e6',
            'desc' => null,
        ];
    }
}
