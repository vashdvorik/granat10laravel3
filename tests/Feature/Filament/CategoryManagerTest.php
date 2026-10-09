<?php

namespace Tests\Feature\Filament;

use App\Enums\ProductModerationStatus;
use App\Filament\Resources\CategoryResource\Pages\ManageCategories;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CategoryTreeService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/** Страница «Категории»: дерево с раскрытием, поиск, добавление, перенос, порядок, перенос товаров, удаление. */
class CategoryManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    /** Одежда → [Женская → [Платья, Юбки], Мужская], Техника */
    private Category $clothes;

    private Category $women;

    private Category $dresses;

    private Category $skirts;

    private Category $men;

    private Category $tech;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
        Cache::flush();

        $role = Role::query()->create(['name' => 'admin-'.uniqid(), 'display_name' => 'Администратор']);
        $role->permissions()->attach(Permission::query()->firstOrCreate(['key' => 'browse_admin'], ['table_name' => null]));
        $this->admin = User::factory()->create(['role_id' => $role->id]);

        $service = app(CategoryTreeService::class);
        $this->clothes = $service->create($this->admin, ['name' => 'Одежда', 'alias' => 'clothes']);
        $this->women = $service->create($this->admin, ['name' => 'Женская одежда', 'alias' => 'women', 'parent_id' => $this->clothes->id]);
        $this->dresses = $service->create($this->admin, ['name' => 'Платья', 'alias' => 'dresses', 'parent_id' => $this->women->id]);
        $this->skirts = $service->create($this->admin, ['name' => 'Юбки', 'alias' => 'skirts', 'parent_id' => $this->women->id]);
        $this->men = $service->create($this->admin, ['name' => 'Мужская одежда', 'alias' => 'men', 'parent_id' => $this->clothes->id]);
        $this->tech = $service->create($this->admin, ['name' => 'Техника', 'alias' => 'tech']);

        $this->actingAs($this->admin);
    }

    private function product(Category $category, array $overrides = []): Product
    {
        return Product::factory()->create(array_merge(['category_id' => $category->id], $overrides));
    }

    private function page()
    {
        return Livewire::test(ManageCategories::class);
    }

    /** @return array<int, string> алиасы категорий в порядке дерева */
    private function treeOrder(?int $parentId = null): array
    {
        return Category::query()
            ->when($parentId === null, fn ($q) => $q->whereNull('parent_id'), fn ($q) => $q->where('parent_id', $parentId))
            ->defaultOrder()
            ->pluck('alias')
            ->all();
    }

    // ------------------------------------------------------------------ дерево

    public function test_only_roots_are_shown_until_a_branch_is_expanded(): void
    {
        $this->page()
            ->assertSee('Одежда')
            ->assertSee('Техника')
            ->assertDontSee('Женская одежда')
            ->call('toggle', $this->clothes->id)
            ->assertSee('Женская одежда')
            ->assertSee('Мужская одежда')
            ->assertDontSee('Платья')
            ->call('toggle', $this->women->id)
            ->assertSee('Платья')
            ->assertSee('Юбки')
            ->call('toggle', $this->clothes->id)
            ->assertDontSee('Женская одежда');
    }

    public function test_counts_show_own_products_and_the_total_with_subcategories(): void
    {
        $this->product($this->dresses);
        $this->product($this->dresses);
        $this->product($this->skirts);
        $this->product($this->tech);

        $this->page()
            ->call('toggle', $this->clothes->id)
            ->call('toggle', $this->women->id)
            ->assertSee('2 подкатегории · всего 3')
            ->assertSeeHtml('<strong>2</strong> товара')   // Платья
            ->assertSeeHtml('<strong>1</strong> товар')    // Юбки, Техника
            ->assertSeeHtml('<strong>0</strong> товаров'); // Одежда, Женская, Мужская
    }

    public function test_search_finds_nested_categories_by_name_or_alias_and_shows_their_path(): void
    {
        $this->page()
            ->set('search', 'платья')
            ->assertSee('Платья')
            ->assertSee('Одежда → Женская одежда')
            ->assertDontSee('Техника')
            ->set('search', 'tech')
            ->assertSee('Техника')
            ->assertDontSee('Платья')
            ->set('search', 'нет такой категории')
            ->assertSee('Ничего не найдено');
    }

    public function test_page_can_be_opened_focused_on_a_category(): void
    {
        Livewire::withQueryParams(['focus' => $this->dresses->id])
            ->test(ManageCategories::class)
            ->assertSee('Платья')
            ->assertSee('Юбки');

        $this->actingAs($this->admin)
            ->get('/admin/categories?focus='.$this->dresses->id)
            ->assertOk()
            ->assertSee('Платья');
    }

    // ------------------------------------------------------------------ добавление

    public function test_add_root_category_with_automatic_alias(): void
    {
        $this->page()
            ->callAction('addCategory', ['name' => 'Книги и журналы', 'alias' => '', 'is_active' => true])
            ->assertHasNoActionErrors()
            ->assertNotified('Категория «Книги и журналы» создана');

        $created = Category::query()->where('name', 'Книги и журналы')->firstOrFail();
        $this->assertNull($created->parent_id);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $created->alias);
        $this->assertSame(['clothes', 'tech', $created->alias], $this->treeOrder());
    }

    public function test_add_child_expands_the_parent_and_keeps_order_numbers_consistent(): void
    {
        $this->page()
            ->callAction('addCategory', ['name' => 'Обувь', 'alias' => 'shoes', 'is_active' => true], ['parent' => $this->clothes->id])
            ->assertHasNoActionErrors()
            ->assertSee('Обувь');

        $this->assertSame(['women', 'men', 'shoes'], $this->treeOrder($this->clothes->id));
        $this->assertSame(
            [1, 2, 3],
            Category::query()->where('parent_id', $this->clothes->id)->defaultOrder()->pluck('number')->map(fn ($n) => (int) $n)->all()
        );
    }

    public function test_add_child_moves_products_of_the_parent_into_it(): void
    {
        $first = $this->product($this->men);
        $second = $this->product($this->men, ['is_active' => true]);

        $this->page()
            ->callAction('addCategory', ['name' => 'Куртки', 'alias' => 'jackets', 'is_active' => true], ['parent' => $this->men->id])
            ->assertHasNoActionErrors()
            ->assertNotified('Категория «Куртки» создана');

        $jackets = Category::query()->where('alias', 'jackets')->firstOrFail();
        $this->assertSame($jackets->id, $first->fresh()->category_id);
        $this->assertSame($jackets->id, $second->fresh()->category_id);
        $this->assertSame(0, Product::query()->where('category_id', $this->men->id)->count());
        // категория-родитель больше не хранит товары: «осиротевших» нет
        $this->assertCount(0, app(CategoryTreeService::class)->categoriesWithOrphanedProducts());
        $this->assertTrue(Activity::query()->where('log_name', 'categories')->where('description', 'like', 'Товары (2) перенесены%')->exists());
    }

    public function test_alias_must_be_unique_when_adding(): void
    {
        $this->page()
            ->callAction('addCategory', ['name' => 'Ещё одна техника', 'alias' => 'tech', 'is_active' => true])
            ->assertHasActionErrors(['alias']);

        $this->assertSame(1, Category::query()->where('alias', 'tech')->count());
    }

    // ------------------------------------------------------------------ перенос категории

    public function test_move_category_to_another_parent_and_to_the_root(): void
    {
        $this->page()
            ->callAction('moveCategory', ['parent_id' => $this->tech->id], ['category' => $this->men->id])
            ->assertHasNoActionErrors()
            ->assertNotified('Категория перемещена');

        $this->assertSame($this->tech->id, $this->men->fresh()->parent_id);
        $this->assertSame(['women'], $this->treeOrder($this->clothes->id));

        $this->page()
            ->callAction('moveCategory', ['parent_id' => null], ['category' => $this->men->id])
            ->assertHasNoActionErrors();

        $this->assertNull($this->men->fresh()->parent_id);
        $this->assertSame(['clothes', 'tech', 'men'], $this->treeOrder());
    }

    public function test_move_with_subtree_keeps_children_and_updates_ancestor_counts_cache(): void
    {
        $this->product($this->dresses);
        // счётчики в кеше: у старой и у новой цепочки родителей
        foreach ([$this->clothes, $this->women, $this->tech] as $category) {
            Cache::put('category_products_count:'.$category->id, 99);
        }

        $this->page()
            ->callAction('moveCategory', ['parent_id' => $this->tech->id], ['category' => $this->women->id])
            ->assertHasNoActionErrors();

        $women = $this->women->fresh();
        $this->assertSame($this->tech->id, $women->parent_id);
        $this->assertTrue($this->dresses->fresh()->isDescendantOf($this->tech->fresh()));
        foreach ([$this->clothes, $this->tech] as $category) {
            $this->assertFalse(Cache::has('category_products_count:'.$category->id), 'счётчик «'.$category->name.'» должен сброситься');
        }
    }

    public function test_cannot_move_a_category_into_itself_into_its_subtree_or_into_a_category_with_products(): void
    {
        $this->product($this->tech);

        $this->page()
            ->callAction('moveCategory', ['parent_id' => $this->dresses->id], ['category' => $this->clothes->id])
            ->assertActionHalted('moveCategory')
            ->assertNotified('Категория не перемещена');

        $this->page()
            ->callAction('moveCategory', ['parent_id' => $this->clothes->id], ['category' => $this->clothes->id])
            ->assertActionHalted('moveCategory');

        // в «Технике» лежат товары: подкатегория сделала бы их «осиротевшими»
        $this->page()
            ->callAction('moveCategory', ['parent_id' => $this->tech->id], ['category' => $this->men->id])
            ->assertActionHalted('moveCategory')
            ->assertNotified('Категория не перемещена');

        $this->assertSame($this->clothes->id, $this->men->fresh()->parent_id);
        $this->assertNull($this->clothes->fresh()->parent_id);
    }

    // ------------------------------------------------------------------ порядок

    public function test_categories_are_reordered_with_arrows_and_numbers_follow(): void
    {
        $this->page()->call('moveDown', $this->women->id);

        $this->assertSame(['men', 'women'], $this->treeOrder($this->clothes->id));
        $this->assertSame([1, 2], Category::query()->where('parent_id', $this->clothes->id)->defaultOrder()->pluck('number')->map(fn ($n) => (int) $n)->all());

        $this->page()->call('moveUp', $this->women->id);
        $this->assertSame(['women', 'men'], $this->treeOrder($this->clothes->id));

        // корни: Техника выше Одежды; подкатегории едут вместе с родителем
        $this->page()->call('moveUp', $this->tech->id);
        $this->assertSame(['tech', 'clothes'], $this->treeOrder());
        $this->assertTrue($this->dresses->fresh()->isDescendantOf($this->clothes->fresh()));
    }

    public function test_arrow_at_the_edge_warns_instead_of_failing(): void
    {
        $this->page()
            ->call('moveUp', $this->clothes->id)
            ->assertNotified('Выше уже некуда')
            ->call('moveDown', $this->tech->id)
            ->assertNotified('Ниже уже некуда');

        $this->assertSame(['clothes', 'tech'], $this->treeOrder());
    }

    // ------------------------------------------------------------------ товары

    public function test_transfer_products_keeps_moderation_and_clears_caches(): void
    {
        $approved = $this->product($this->dresses, [
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 3,
            'moderated_version' => 3,
            'is_active' => true,
        ]);
        $this->product($this->dresses);
        foreach ([$this->dresses, $this->women, $this->clothes, $this->skirts] as $category) {
            Cache::put('category_products_count:'.$category->id, 77);
        }

        $this->page()
            ->callAction('transferProducts', ['target' => $this->skirts->id], ['category' => $this->dresses->id])
            ->assertHasNoActionErrors()
            ->assertNotified('Товары перенесены: 2');

        $this->assertSame(0, Product::query()->where('category_id', $this->dresses->id)->count());
        $this->assertSame(2, Product::query()->where('category_id', $this->skirts->id)->count());

        $approved->refresh();
        $this->assertSame(ProductModerationStatus::Approved, $approved->moderation_status);
        $this->assertSame(3, $approved->moderation_version);
        $this->assertSame(3, $approved->moderated_version);
        $this->assertTrue($approved->is_active, 'публикация товара не меняется');

        foreach ([$this->dresses, $this->skirts, $this->women, $this->clothes] as $category) {
            $this->assertFalse(Cache::has('category_products_count:'.$category->id), 'счётчик «'.$category->name.'» должен сброситься');
        }
    }

    public function test_transfer_is_refused_into_a_category_with_subcategories_or_into_itself(): void
    {
        $product = $this->product($this->dresses);

        $this->page()
            ->callAction('transferProducts', ['target' => $this->women->id], ['category' => $this->dresses->id])
            ->assertActionHalted('transferProducts')
            ->assertNotified('Товары не перенесены');

        $this->page()
            ->callAction('transferProducts', ['target' => $this->dresses->id], ['category' => $this->dresses->id])
            ->assertActionHalted('transferProducts');

        $this->assertSame($this->dresses->id, $product->fresh()->category_id);
    }

    public function test_orphaned_products_are_highlighted_and_can_be_fixed(): void
    {
        // как бывало раньше: товары лежат в категории, у которой потом появились подкатегории
        $orphan = $this->product($this->clothes);
        $this->assertCount(1, app(CategoryTreeService::class)->categoriesWithOrphanedProducts());

        $this->page()
            ->assertSee('Категорий с «осиротевшими» товарами: 1')
            ->assertSee('Товары не в подкатегории')
            ->set('onlyProblems', true)
            ->assertSee('Одежда')
            ->assertDontSee('Техника')
            ->callAction('transferProducts', ['target' => $this->men->id], ['category' => $this->clothes->id])
            ->assertHasNoActionErrors();

        $this->assertSame($this->men->id, $orphan->fresh()->category_id);
        $this->assertCount(0, app(CategoryTreeService::class)->categoriesWithOrphanedProducts());

        $this->page()->assertDontSee('осиротевшими');
    }

    // ------------------------------------------------------------------ скрытие и удаление

    public function test_hide_and_show_a_category(): void
    {
        $this->page()
            ->callAction('toggleActive', arguments: ['category' => $this->tech->id])
            ->assertNotified('Категория скрыта');

        $this->assertFalse($this->tech->fresh()->is_active);

        $this->page()
            ->assertSee('Скрыта')
            ->callAction('toggleActive', arguments: ['category' => $this->tech->id])
            ->assertNotified('Категория показана');

        $this->assertTrue($this->tech->fresh()->is_active);
    }

    public function test_delete_is_refused_for_categories_with_children_and_asks_where_to_move_products(): void
    {
        $this->page()
            ->callAction('deleteCategory', arguments: ['category' => $this->clothes->id])
            ->assertActionHalted('deleteCategory')
            ->assertNotified('Категория не удалена');

        $product = $this->product($this->tech);

        // с товарами нужно выбрать, куда их перенести
        $this->page()
            ->callAction('deleteCategory', [], ['category' => $this->tech->id])
            ->assertHasActionErrors(['move_to']);

        $this->assertDatabaseHas('categories', ['id' => $this->tech->id]);

        $this->page()
            ->callAction('deleteCategory', ['move_to' => $this->men->id], ['category' => $this->tech->id])
            ->assertHasNoActionErrors()
            ->assertNotified('Категория «Техника» удалена');

        $this->assertDatabaseMissing('categories', ['id' => $this->tech->id]);
        $this->assertSame($this->men->id, $product->fresh()->category_id);
    }

    public function test_empty_category_is_deleted_after_confirmation(): void
    {
        $this->page()
            ->callAction('deleteCategory', arguments: ['category' => $this->skirts->id])
            ->assertHasNoActionErrors();

        $this->assertDatabaseMissing('categories', ['id' => $this->skirts->id]);
        $this->assertSame(['dresses'], $this->treeOrder($this->women->id));
    }

    // ------------------------------------------------------------------ доступ

    public function test_regular_user_cannot_manage_categories(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/admin/categories')->assertForbidden();
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        app(CategoryTreeService::class)->move($user, $this->men, null);
    }
}
