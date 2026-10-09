<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Support\CategoryPicker;
use App\Support\Plural;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Поле «выберите категорию» в админке ищет по названию и показывает путь; склонение чисел для счётчиков. */
class CategoryPickerTest extends TestCase
{
    use RefreshDatabase;

    private Category $clothes;

    private Category $women;

    private Category $dresses;

    private Category $tech;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clothes = Category::factory()->create(['name' => 'Одежда', 'alias' => 'clothes', 'parent_id' => null]);
        $this->women = Category::factory()->create(['name' => 'Женская', 'alias' => 'women', 'parent_id' => $this->clothes->id]);
        $this->dresses = Category::factory()->create(['name' => 'Платья', 'alias' => 'dresses', 'parent_id' => $this->women->id]);
        $this->tech = Category::factory()->create(['name' => 'Техника', 'alias' => 'tech', 'parent_id' => null]);
    }

    public function test_without_a_query_only_root_categories_are_offered_as_parents(): void
    {
        $this->assertSame(
            [$this->clothes->id => 'Одежда', $this->tech->id => 'Техника'],
            CategoryPicker::parents('')
        );
    }

    public function test_search_finds_by_name_or_alias_and_shows_the_path(): void
    {
        $this->assertSame([$this->dresses->id => 'Одежда → Женская → Платья'], CategoryPicker::parents('платья'));
        $this->assertSame([$this->tech->id => 'Техника'], CategoryPicker::parents('TECH'));
        $this->assertSame([], CategoryPicker::parents('нет такой'));
    }

    public function test_parent_choices_never_include_the_category_itself_or_its_subtree(): void
    {
        $options = CategoryPicker::parents('', $this->clothes->fresh());
        $this->assertArrayNotHasKey($this->clothes->id, $options);

        $found = CategoryPicker::parents('а', $this->clothes->fresh());
        $this->assertArrayNotHasKey($this->women->id, $found);
        $this->assertArrayNotHasKey($this->dresses->id, $found);
        $this->assertArrayHasKey($this->tech->id, $found);
    }

    public function test_product_targets_are_leaf_categories_and_may_lie_inside_the_source_branch(): void
    {
        // «Одежда» с подкатегориями: её товары переносят именно в подкатегорию
        $targets = CategoryPicker::leaves('а', $this->clothes->fresh());

        $this->assertArrayHasKey($this->dresses->id, $targets);
        $this->assertArrayHasKey($this->tech->id, $targets);
        $this->assertArrayNotHasKey($this->clothes->id, $targets);
        $this->assertArrayNotHasKey($this->women->id, $targets, 'категория с подкатегориями не может хранить товары');
        $this->assertSame([], CategoryPicker::leaves(''), 'без запроса ничего не предлагаем');
    }

    public function test_path_of_a_selected_category(): void
    {
        $this->assertSame('Одежда → Женская → Платья', CategoryPicker::path($this->dresses->id));
        $this->assertNull(CategoryPicker::path(null));
        $this->assertNull(CategoryPicker::path(99999));
    }

    public function test_results_are_limited(): void
    {
        foreach (range(1, 70) as $i) {
            Category::factory()->create(['name' => 'Товар '.$i, 'alias' => 'item-'.$i, 'parent_id' => $this->tech->id]);
        }

        $this->assertCount(50, CategoryPicker::leaves('Товар'));
    }

    public function test_russian_plural_forms(): void
    {
        $forms = fn (int $n): string => Plural::ru($n, 'товар', 'товара', 'товаров');

        $this->assertSame('товаров', $forms(0));
        $this->assertSame('товар', $forms(1));
        $this->assertSame('товара', $forms(3));
        $this->assertSame('товаров', $forms(5));
        $this->assertSame('товаров', $forms(11));
        $this->assertSame('товаров', $forms(14));
        $this->assertSame('товар', $forms(21));
        $this->assertSame('товара', $forms(22));
        $this->assertSame('товаров', $forms(112));
        $this->assertSame('товар', $forms(101));
    }
}
