<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Region;
use App\Models\Settlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogRegionalSelectorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('regionality.enabled', true);
        config()->set('regionality.urls_enabled', true);
    }

    public function test_catalog_displays_grouped_regional_selector(): void
    {
        $region = Region::factory()->create(['name' => 'Слободзейский район']);
        $settlement = Settlement::factory()->for($region)->create([
            'name' => 'Слободзея',
            'type' => 'город',
        ]);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Все регионы')
            ->assertSee($region->name)
            ->assertSee($settlement->name)
            ->assertSee('class="pd-sort"', false)
            ->assertSee('class="filter-sort"', false)
            ->assertSee('class="catalog-region-heading"', false)
            ->assertDontSee('catalog-region-selector__');
    }

    public function test_regional_catalog_displays_selected_settlement(): void
    {
        $settlement = Settlement::factory()->create([
            'name' => 'Тирасполь',
            'slug' => 'tiraspol',
        ]);

        $this->get(route('regional.catalog.index', ['location' => $settlement->slug]))
            ->assertOk()
            ->assertSee('dropdown-label">'.$settlement->name, false)
            ->assertSee('return_to', false);
    }

    public function test_category_selector_keeps_category_url_when_switching_city(): void
    {
        $oldSettlement = Settlement::factory()->create(['slug' => 'old-city']);
        $newSettlement = Settlement::factory()->create(['slug' => 'new-city']);
        $category = Category::factory()->create(['is_active' => true]);
        $returnTo = route('regional.catalog.show', [
            'location' => $oldSettlement->slug,
            'alias' => $category->alias,
        ]);

        $this->post(route('regionality.update'), [
            'settlement_id' => $newSettlement->id,
            'return_to' => $returnTo,
        ])->assertRedirect(route('regional.catalog.show', [
            'location' => $newSettlement->slug,
            'alias' => $category->alias,
        ]));
    }
}
