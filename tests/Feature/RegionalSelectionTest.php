<?php

namespace Tests\Feature;

use App\Models\Region;
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
    }

    public function test_visitor_can_select_settlement(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Тирасполь']);

        $response = $this
            ->from(route('home'))
            ->post(route('regionality.update'), [
                'settlement_id' => $settlement->id,
            ]);

        $response
            ->assertRedirect(route('regional.home', ['location' => $settlement->slug]))
            ->assertSessionHas('regional_settlement_id', $settlement->id)
            ->assertCookie(config('regionality.cookie_name'), (string) $settlement->id);
    }

    public function test_selected_settlement_is_shown_on_next_request(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Бендеры']);

        $this->withSession(['regional_settlement_id' => $settlement->id])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Бендеры');
    }

    public function test_cookie_restores_settlement_into_session(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Рыбница']);

        $this->withCookie(config('regionality.cookie_name'), (string) $settlement->id)
            ->get(route('home'))
            ->assertOk()
            ->assertSessionHas('regional_settlement_id', $settlement->id)
            ->assertSee('Рыбница');
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
