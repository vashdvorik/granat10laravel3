<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Region;
use App\Models\Seller;
use App\Models\Settlement;
use App\Services\RegionalContext;
use App\Services\SchemaInspector;
use App\Services\SellerPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Число запросов к базе не должно расти вместе с числом категорий.
 * Раньше страница магазина делала по два запроса на каждую категорию (на настоящем каталоге, 1600 категорий,
 * это более 3000 запросов), а фильтр пустых категорий — по три.
 */
class PageQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    /** Дерево: $roots корней, у каждого по $children потомков. Возвращает [корни, все потомки]. */
    private function tree(int $roots, int $children): array
    {
        $rootModels = [];
        $leaves = [];

        foreach (range(1, $roots) as $r) {
            $root = Category::factory()->create(['alias' => "root-$r", 'parent_id' => null, 'is_active' => true]);
            $rootModels[] = $root;

            foreach (range(1, $children) as $c) {
                $leaves[] = Category::factory()->create(['alias' => "root-$r-child-$c", 'parent_id' => $root->id, 'is_active' => true]);
            }
        }

        return [$rootModels, $leaves];
    }

    /** @return array{0: mixed, 1: int} результат и число запросов */
    private function counting(callable $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $result = $callback();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$result, $queries];
    }

    // ------------------------------------------------------------------ страница магазина

    public function test_shop_categories_are_counted_with_a_constant_number_of_queries(): void
    {
        [$roots, $leaves] = $this->tree(6, 8);   // 54 категории
        $seller = Seller::factory()->create(['description' => null]);
        $other = Seller::factory()->create(['description' => null]);

        // root-1: два товара в разных подкатегориях и один в самом корне; root-2: один товар
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $leaves[0]->id]);
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $leaves[5]->id]);
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $roots[0]->id]);
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $leaves[8]->id]);
        // не считаются: снятый с публикации товар магазина и товар чужого магазина
        Product::factory()->inactive()->create(['seller_id' => $seller->id, 'category_id' => $roots[3]->id]);
        Product::factory()->create(['seller_id' => $other->id, 'category_id' => $roots[4]->id]);
        Cache::flush();

        [$categories, $queries] = $this->counting(fn () => app(SellerPageService::class)->getSellerCategories($seller));

        $this->assertEqualsCanonicalizing(
            [$roots[0]->id => 3, $roots[1]->id => 1],
            $categories->mapWithKeys(fn ($category) => [$category->id => $category->total_products])->all()
        );
        $this->assertLessThanOrEqual(4, $queries, 'число запросов не должно зависеть от числа категорий');
    }

    public function test_shop_page_stays_within_a_small_query_budget_on_a_big_category_tree(): void
    {
        [, $leaves] = $this->tree(10, 12);   // 130 категорий
        $seller = Seller::factory()->create(['alias' => 'big-tree-shop', 'description' => null]);

        foreach (array_slice($leaves, 0, 30) as $leaf) {
            Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $leaf->id]);
        }

        $this->get(route('seller', 'big-tree-shop'))->assertOk();   // прогрев кеша
        [$response, $queries] = $this->counting(fn () => $this->get(route('seller', 'big-tree-shop')));

        $response->assertOk();
        $this->assertLessThanOrEqual(40, $queries, "страница магазина сделала $queries запросов");
    }

    // ------------------------------------------------------------------ фильтр пустых категорий

    public function test_empty_categories_are_filtered_with_a_constant_number_of_queries(): void
    {
        [$roots, $leaves] = $this->tree(8, 6);   // 56 категорий
        $seller = Seller::factory()->create();

        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $leaves[2]->id]);                  // root-1: товар в ребёнке
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $roots[2]->id]);                   // root-3: товар в самом корне
        Product::factory()->inactive()->create(['seller_id' => $seller->id, 'category_id' => $leaves[20]->id]);     // root-4: только снятый товар
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $leaves[30]->id]);                 // root-6: товар в ребёнке
        Cache::flush();

        $tree = Category::where('is_active', true)->defaultOrder()->get()->toTree();

        [$filtered, $queries] = $this->counting(fn () => Category::filterTreeByProducts($tree));

        $this->assertSame(
            ['root-1', 'root-3', 'root-6'],
            $filtered->pluck('alias')->all()
        );
        // у корня с товаром в ребёнке остаётся только этот ребёнок
        $this->assertSame(['root-1-child-3'], $filtered->first()->children->pluck('alias')->all());
        $this->assertLessThanOrEqual(3, $queries, 'число запросов не должно зависеть от числа категорий');
    }

    public function test_products_of_a_hidden_shop_do_not_keep_a_category_visible(): void
    {
        [$roots] = $this->tree(2, 2);
        $hidden = Seller::factory()->create();
        $hidden->forceFill(['hidden_at' => now(), 'hidden_reason' => 'тест'])->save();
        Product::factory()->create(['seller_id' => $hidden->id, 'category_id' => $roots[0]->id]);
        Cache::flush();

        $filtered = Category::filterTreeByProducts(Category::where('is_active', true)->defaultOrder()->get()->toTree());

        $this->assertTrue($filtered->isEmpty());
    }

    // ------------------------------------------------------------------ счётчики категорий

    public function test_batch_category_counts_match_single_counts_and_cost_few_queries(): void
    {
        [$roots, $leaves] = $this->tree(5, 6);
        $seller = Seller::factory()->create();
        Product::factory()->count(2)->create(['seller_id' => $seller->id, 'category_id' => $leaves[0]->id]);
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $leaves[7]->id]);
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $roots[0]->id]);
        Product::factory()->inactive()->create(['seller_id' => $seller->id, 'category_id' => $roots[2]->id]);

        $ids = collect($roots)->pluck('id')->merge(collect($leaves)->pluck('id'))->all();

        Cache::flush();
        $single = [];
        foreach ($ids as $id) {
            $single[$id] = Product::getCategoryProductsCount($id);
        }

        Cache::flush();
        [$batch, $queries] = $this->counting(fn () => Product::getCategoryProductsCounts($ids));

        $this->assertSame($single, $batch);
        $this->assertSame(3, $batch[$roots[0]->id]);
        $this->assertSame(0, $batch[$roots[2]->id]);
        $this->assertLessThanOrEqual(4, $queries);

        // повторный вызов берёт всё из кеша
        [, $cached] = $this->counting(fn () => Product::getCategoryProductsCounts($ids));
        $this->assertSame(0, $cached);
    }

    // ------------------------------------------------------------------ служебные проверки

    public function test_table_existence_is_asked_from_the_database_only_once(): void
    {
        $schemaQueries = fn () => collect(DB::getQueryLog())
            ->filter(fn (array $q): bool => str_contains($q['query'], 'sqlite_master') || str_contains($q['query'], 'information_schema'))
            ->count();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $first = new SchemaInspector();
        $this->assertTrue($first->hasTable('site_settings'));
        $this->assertTrue($first->hasTable('site_settings'));
        $this->assertSame(1, $schemaQueries(), 'внутри запроса таблица проверяется один раз');

        // следующий запрос берёт положительный ответ из кеша
        DB::flushQueryLog();
        $this->assertTrue((new SchemaInspector())->hasTable('site_settings'));
        $this->assertSame(0, $schemaQueries(), 'между запросами ответ приходит из кеша');

        // отрицательный ответ не кешируется: пока таблицы нет, спрашиваем каждый раз
        DB::flushQueryLog();
        $this->assertFalse((new SchemaInspector())->hasTable('no_such_table'));
        $this->assertFalse((new SchemaInspector())->hasTable('no_such_table'));
        $this->assertSame(2, $schemaQueries());

        DB::disableQueryLog();
    }

    public function test_regions_for_the_header_are_loaded_once_per_request(): void
    {
        $region = Region::factory()->create(['is_active' => true]);
        Settlement::factory()->create(['region_id' => $region->id, 'is_active' => true]);

        app()->instance('request', Request::create('/'));
        (new RegionalContext())->availableRegions();   // прогрев проверки таблиц: считаем только сами регионы

        [, $oneCall] = $this->counting(fn () => (new RegionalContext())->availableRegions());

        $context = new RegionalContext();
        app()->instance('request', Request::create('/'));
        [, $threeCalls] = $this->counting(function () use ($context) {
            $context->availableRegions();
            $context->availableRegions();
            $context->availableRegions();
        });

        $this->assertSame($oneCall, $threeCalls, 'повторные вызовы в одном запросе не должны ходить в базу');

        // новый запрос — свежие данные (в том числе добавленные между запросами)
        Settlement::factory()->create(['region_id' => $region->id, 'is_active' => true, 'name' => 'Новый пункт']);
        app()->instance('request', Request::create('/other'));

        $this->assertTrue($context->availableRegions()->first()->settlements->contains('name', 'Новый пункт'));
    }
}
