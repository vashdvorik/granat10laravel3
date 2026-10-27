<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Region;
use App\Models\Seller;
use App\Models\Settlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegionalSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('regionality.enabled', true);
        config()->set('regionality.urls_enabled', true);
    }

    public function test_visitor_can_select_settlement(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Тирасполь']);

        $response = $this
            ->from(route('home'))
            ->post(route('regionality.update'), [
                'settlement_id' => $settlement->id,
            ]);

        // Город живёт в адресе; cookie нужна только для подсказки «в прошлый раз вы выбирали...»
        $response
            ->assertRedirect(route('regional.home', ['location' => $settlement->slug]))
            ->assertSessionMissing('regional_settlement_id')
            ->assertCookie(config('regionality.cookie_name'), (string) $settlement->id);
    }

    public function test_selected_settlement_is_shown_on_its_regional_address(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Бендеры', 'slug' => 'bendery-test']);

        $this->get(route('regional.home', ['location' => $settlement->slug]))
            ->assertOk()
            ->assertSee('Бендеры');
    }

    public function test_stored_city_is_only_a_suggestion_on_the_home_page(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Рыбница', 'slug' => 'rybnitsa-test']);

        // cookie со старым выбором: общая главная остаётся «Все регионы», город только предлагается
        $this->withCookie(config('regionality.cookie_name'), (string) $settlement->id)
            ->get(route('home'))
            ->assertOk()
            ->assertSessionMissing('regional_settlement_id')
            ->assertSee('region-notice--suggestion', false)
            ->assertSee('В прошлый раз вы выбирали Рыбница.')
            ->assertSee('href="'.route('regional.home', ['location' => 'rybnitsa-test']).'"', false)
            ->assertDontSee('Показаны товары и магазины только');
    }

    public function test_suggestion_is_absent_without_a_stored_city_on_a_regional_page_and_on_other_pages(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Рыбница', 'slug' => 'rybnitsa-test']);
        $cookie = [config('regionality.cookie_name'), (string) $settlement->id];

        $this->get(route('home'))->assertOk()->assertDontSee('region-notice--suggestion', false);
        $this->withCookie(...$cookie)->get(route('regional.home', ['location' => 'rybnitsa-test']))
            ->assertOk()->assertDontSee('region-notice--suggestion', false);
        $this->withCookie(...$cookie)->get(route('catalog.index'))
            ->assertOk()->assertDontSee('region-notice--suggestion', false);
    }

    public function test_dismissing_the_suggestion_forgets_the_city(): void
    {
        $settlement = Settlement::factory()->create();

        $this->withCookie(config('regionality.cookie_name'), (string) $settlement->id)
            ->from(route('home'))
            ->post(route('regionality.update'), ['return_to' => route('home')])
            ->assertRedirect(route('home'))
            ->assertCookieExpired(config('regionality.cookie_name'));
    }

    public function test_notice_with_a_reset_button_is_shown_on_regional_pages_only(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Тирасполь', 'slug' => 'tiraspol-test']);

        foreach (['home', 'catalog.index', 'search', 'catalog.sale', 'shops', 'favorites'] as $name) {
            // в каталоге без товаров плашку заменяет карточка «товаров нет» с тем же действием
            $marker = $name === 'catalog.index' ? 'catalog-empty' : 'region-notice';

            $this->get(route('regional.'.$name, ['location' => 'tiraspol-test']))
                ->assertOk()
                ->assertSee($marker, false)
                ->assertSee('Показать все регионы');

            $this->get(route($name))->assertOk()->assertDontSee('Показать все регионы');
        }
    }

    public function test_notice_points_out_a_short_list_and_offers_all_regions(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Тирасполь', 'slug' => 'tiraspol-test']);
        $seller = Seller::factory()->for($settlement)->create();
        $category = Category::factory()->create();
        Product::factory()->count(2)->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_active' => true]);

        $this->get(route('regional.catalog.index', ['location' => 'tiraspol-test']))
            ->assertOk()
            ->assertSee('region-notice--few', false)
            ->assertSee('В Тирасполе найдено 2 товара.')
            ->assertSee('Показать все регионы');
    }

    public function test_header_has_city_switcher_in_place_of_the_category_dropdown(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Тирасполь', 'slug' => 'tiraspol-test']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('region-switcher--header', false)
            ->assertDontSee('header-region-bar', false)
            ->assertSee('id="region-switch-all"', false)
            ->assertSee('id="region-switch-'.$settlement->id.'"', false)
            ->assertSee('form="region-switch-'.$settlement->id.'"', false)
            ->assertDontSee('id="search-category-label"', false);

        $this->get(route('regional.home', ['location' => 'tiraspol-test']))
            ->assertOk()
            ->assertSee('<span class="region-switcher__label">Тирасполь</span>', false);
    }

    public function test_header_keeps_the_category_dropdown_when_regionality_is_off(): void
    {
        config()->set('regionality.enabled', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="search-category-label"', false)
            ->assertDontSee('region-switcher--header', false);
    }

    public function test_switching_city_on_a_page_without_a_regional_version_goes_to_the_city_home(): void
    {
        $settlement = Settlement::factory()->create(['slug' => 'tiraspol-test']);

        $this->post(route('regionality.update'), [
            'settlement_id' => $settlement->id,
            'return_to' => route('help'),
        ])->assertRedirect(route('regional.home', ['location' => 'tiraspol-test']));
    }

    public function test_visitor_can_reset_selection_to_all_regions(): void
    {
        $settlement = Settlement::factory()->create();

        $response = $this
            ->withSession(['regional_settlement_id' => $settlement->id])
            ->from(route('home'))
            ->post(route('regionality.update'));

        $response
            ->assertRedirect(route('home'))
            ->assertSessionMissing('regional_settlement_id')
            ->assertCookieExpired(config('regionality.cookie_name'));
    }

    public function test_inactive_settlement_cannot_be_selected(): void
    {
        $settlement = Settlement::factory()->create(['is_active' => false]);

        $this->from(route('home'))
            ->post(route('regionality.update'), [
                'settlement_id' => $settlement->id,
            ])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('settlement_id');
    }

    public function test_settlement_of_inactive_region_is_rejected(): void
    {
        $region = Region::factory()->create(['is_active' => false]);
        $settlement = Settlement::factory()->for($region)->create();

        $this->post(route('regionality.update'), [
            'settlement_id' => $settlement->id,
        ])->assertNotFound();
    }

    public function test_selector_is_unavailable_when_feature_is_disabled(): void
    {
        config()->set('regionality.enabled', false);
        $settlement = Settlement::factory()->create();

        $this->post(route('regionality.update'), [
            'settlement_id' => $settlement->id,
        ])->assertNotFound();
    }

    public function test_switching_settlement_replaces_location_in_current_url(): void
    {
        config()->set('regionality.urls_enabled', true);
        $oldSettlement = Settlement::factory()->create(['slug' => 'old-city']);
        $newSettlement = Settlement::factory()->create(['slug' => 'new-city']);

        $this->post(route('regionality.update'), [
            'settlement_id' => $newSettlement->id,
            'return_to' => route('regional.catalog.index', [
                'location' => $oldSettlement->slug,
                'page' => 2,
            ]),
        ])->assertRedirect(route('regional.catalog.index', [
            'location' => $newSettlement->slug,
            'page' => 2,
        ]));
    }

    public function test_resetting_settlement_removes_location_from_current_url(): void
    {
        config()->set('regionality.urls_enabled', true);
        $settlement = Settlement::factory()->create(['slug' => 'old-city']);

        $this->post(route('regionality.update'), [
            'return_to' => route('regional.catalog.index', [
                'location' => $settlement->slug,
                'page' => 2,
            ]),
        ])->assertRedirect(route('catalog.index', ['page' => 2]));
    }
}
