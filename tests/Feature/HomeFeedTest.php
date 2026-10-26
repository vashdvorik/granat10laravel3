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
 * Лента «Новинки» на главной: заголовок с городом, быстрые фильтры, значок доставки на карточках,
 * блок «Вы смотрели» для вернувшихся посетителей.
 */
class HomeFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('regionality.enabled', true);
        config()->set('regionality.urls_enabled', true);
        Cache::flush();
    }

    private function product(Category $category, string $name, array $sellerState = [], array $productState = [], ?Settlement $settlement = null): Product
    {
        $seller = Seller::factory()->create(array_merge(['settlement_id' => $settlement?->id], $sellerState));

        return Product::factory()->create(array_merge([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'name' => $name,
            'is_active' => true,
        ], $productState));
    }

    public function test_heading_names_the_region_and_old_texts_are_gone(): void
    {
        $category = Category::factory()->create(['parent_id' => null]);
        $tiraspol = Settlement::factory()->create(['name' => 'Тирасполь', 'slug' => 'tiraspol-feed']);
        $this->product($category, 'Новинка из Тирасполя', settlement: $tiraspol);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Новинки в Приднестровье')
            ->assertDontSee('Появилось недавно')
            ->assertDontSee('Смотреть каталог');

        $this->get(route('regional.home', ['location' => 'tiraspol-feed']))
            ->assertOk()
            ->assertSee('Новинки в Тирасполе')
            ->assertDontSee('Новинки в Приднестровье');
    }

    public function test_quick_filters_list_only_root_categories_that_have_products(): void
    {
        $clothes = Category::factory()->create(['parent_id' => null, 'name' => 'Одежда', 'alias' => 'clothes-feed', 'number' => 1]);
        $women = Category::factory()->create(['parent_id' => $clothes->id, 'name' => 'Женская']);
        $tech = Category::factory()->create(['parent_id' => null, 'name' => 'Техника', 'alias' => 'tech-feed', 'number' => 2]);
        Category::factory()->create(['parent_id' => null, 'name' => 'Пустая категория', 'alias' => 'empty-feed', 'number' => 3]);

        $this->product($women, 'Платье');
        $this->product($tech, 'Телефон');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="home-chips"', false)
            ->assertSee('data-feed-url="'.route('home.latest').'"', false)
            ->assertSee('data-category="clothes-feed"', false)
            ->assertSee('data-category="tech-feed"', false)
            ->assertDontSee('empty-feed', false)
            ->assertDontSee('Пустая категория');
    }

    public function test_home_shows_root_categories_as_picture_tiles_without_product_counts(): void
    {
        $clothes = Category::factory()->create(['parent_id' => null, 'name' => 'Одежда', 'alias' => 'clothes-tile', 'icon' => 'img_category/1.webp', 'bg_color' => '#F0EEFF', 'number' => 1]);
        $women = Category::factory()->create(['parent_id' => $clothes->id, 'name' => 'Женская вложенная']);
        Category::factory()->create(['parent_id' => null, 'name' => 'Пустая плитка', 'alias' => 'empty-tile', 'number' => 2]);
        $this->product($women, 'Платье');

        $html = $this->get(route('home'))->assertOk()->assertSee('Категории товаров')->getContent();

        // смотрим только сам блок: подкатегории и пустые категории есть в бургер-меню, а плиток у них нет
        $this->assertSame(1, preg_match('/<div class="home-cats">(.*?)<\/section>/s', $html, $block));
        $tiles = $block[1];

        // плитка корневой категории с её картинкой и цветом; вложенные и пустые категории плиток не получают
        $this->assertStringContainsString('href="'.route('catalog.show', 'clothes-tile').'"', $tiles);
        $this->assertStringContainsString('storage/img_category/1.webp', $tiles);
        $this->assertStringContainsString('background: #F0EEFF;', $tiles);
        $this->assertStringNotContainsString('Женская вложенная', $tiles);
        $this->assertStringNotContainsString('Пустая плитка', $tiles);
        // последняя плитка ведёт в каталог; прежних карточек с числом товаров нет
        $this->assertStringContainsString('Все категории', $tiles);
        $this->assertStringNotContainsString('home-category__content', $html);
    }

    public function test_quick_filters_are_hidden_when_there_is_nothing_to_choose_from(): void
    {
        $category = Category::factory()->create(['parent_id' => null]);
        $this->product($category, 'Единственный товар');

        $this->get(route('home'))->assertOk()->assertDontSee('class="home-chips"', false);
    }

    public function test_filter_endpoint_returns_cards_of_the_category_tree_only(): void
    {
        $clothes = Category::factory()->create(['parent_id' => null, 'alias' => 'clothes-feed']);
        $women = Category::factory()->create(['parent_id' => $clothes->id]);
        $tech = Category::factory()->create(['parent_id' => null, 'alias' => 'tech-feed']);
        $this->product($women, 'Платье из вложенной категории');
        $this->product($tech, 'Телефон из другой категории');

        $this->get(route('home.latest', ['category' => 'clothes-feed']))
            ->assertOk()
            ->assertSee('Платье из вложенной категории')
            ->assertDontSee('Телефон из другой категории');

        // без параметра — вся лента
        $this->get(route('home.latest'))
            ->assertOk()
            ->assertSee('Платье из вложенной категории')
            ->assertSee('Телефон из другой категории');
    }

    public function test_filter_endpoint_respects_the_city_in_the_address_and_rejects_unknown_categories(): void
    {
        $category = Category::factory()->create(['parent_id' => null, 'alias' => 'feed-cat']);
        $tiraspol = Settlement::factory()->create(['slug' => 'tiraspol-feed']);
        $bendery = Settlement::factory()->create(['slug' => 'bendery-feed']);
        $this->product($category, 'Товар Тирасполя', settlement: $tiraspol);
        $this->product($category, 'Товар Бендер', settlement: $bendery);

        $this->get(route('regional.home.latest', ['location' => 'tiraspol-feed', 'category' => 'feed-cat']))
            ->assertOk()
            ->assertSee('Товар Тирасполя')
            ->assertDontSee('Товар Бендер');

        $this->get(route('home.latest', ['category' => 'нет-такой']))->assertNotFound();
    }

    public function test_cards_show_the_delivery_badge_only_when_both_seller_and_product_allow_delivery(): void
    {
        $category = Category::factory()->create(['parent_id' => null]);
        $this->product($category, 'Товар с доставкой', ['delivery_enabled' => true], ['delivery_enabled' => true]);
        $this->product($category, 'Магазин без доставки', ['delivery_enabled' => false], ['delivery_enabled' => true]);
        $this->product($category, 'Товар без доставки', ['delivery_enabled' => true], ['delivery_enabled' => false]);

        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'class="home-product__delivery"'));
        $this->assertStringContainsString('Есть доставка', $html);
    }

    public function test_product_page_marks_the_view_for_the_recently_viewed_block_and_shows_delivery(): void
    {
        $category = Category::factory()->create(['parent_id' => null]);
        $product = $this->product($category, 'Просмотренный товар', ['delivery_enabled' => true], ['delivery_enabled' => true]);

        $this->get(route('product.show', $product->alias))
            ->assertOk()
            ->assertSee('data-recent-product="'.$product->id.'"', false)
            ->assertSee('class="product-delivery"', false);
    }

    public function test_home_has_a_hidden_returning_visitor_block_filled_in_by_the_browser(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="home-returns" hidden', false)
            ->assertSee('data-products-url="'.route('favorites.products').'"', false)
            ->assertSee('Вы смотрели')
            ->assertSee('home-returns__favorites', false);
    }
}
