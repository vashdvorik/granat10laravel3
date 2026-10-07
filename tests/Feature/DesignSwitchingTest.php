<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveDesign;
use App\Models\SiteSetting;
use App\Services\DesignManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DesignSwitchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_design_is_global_and_is_loaded_from_site_settings(): void
    {
        SiteSetting::query()->create([
            'key' => config('design.setting_key'),
            'value' => 'new',
        ]);
        Cache::forget(config('design.cache_key'));

        $this->assertSame('new', app(DesignManager::class)->current());
    }

    public function test_unknown_stored_design_falls_back_to_legacy(): void
    {
        SiteSetting::query()->create([
            'key' => config('design.setting_key'),
            'value' => '../unknown',
        ]);
        Cache::forget(config('design.cache_key'));

        $this->assertSame('legacy', app(DesignManager::class)->current());
    }

    public function test_new_design_overrides_a_view_and_missing_views_use_legacy_fallback(): void
    {
        Route::middleware(ResolveDesign::class)->get('/_design-test', function () {
            return view('design-test-page');
        });

        $themeView = resource_path('views/themes/new/design-test-page.blade.php');
        $legacyView = resource_path('views/design-test-page.blade.php');

        file_put_contents($legacyView, 'legacy design');
        file_put_contents($themeView, 'new design');
        app(DesignManager::class)->setActive('new');

        try {
            $this->get('/_design-test')
                ->assertOk()
                ->assertSeeText('new design');

            unlink($themeView);

            $this->get('/_design-test')
                ->assertOk()
                ->assertSeeText('legacy design');
        } finally {
            @unlink($themeView);
            @unlink($legacyView);
        }
    }

    public function test_new_design_renders_the_public_premium_shell(): void
    {
        config()->set('design.default', 'new');
        Cache::forget(config('design.cache_key'));

        try {
            $this->get(route('home'))
                ->assertOk()
                ->assertSee('bimm-header')
                ->assertSee('theme-new.css');
        } finally {
            config()->set('design.default', 'legacy');
            Cache::forget(config('design.cache_key'));
        }
    }

    public function test_joom_design_renders_the_public_marketplace_shell(): void
    {
        config()->set('design.default', 'joom');
        Cache::forget(config('design.cache_key'));

        try {
            $this->get(route('home'))
                ->assertOk()
                ->assertSee('joom-header')
                ->assertSee('theme-joom.css')
                ->assertSeeText('Покупки на каждый день');
        } finally {
            config()->set('design.default', 'legacy');
            Cache::forget(config('design.cache_key'));
        }
    }

    public function test_new_cabinet_design_renders_the_seller_cabinet_shell(): void
    {
        config()->set('cabinet_design.default', 'legacy');
        SiteSetting::query()->updateOrCreate([
            'key' => config('cabinet_design.setting_key'),
        ], [
            'value' => 'new',
        ]);
        Cache::forget(config('cabinet_design.cache_key'));

        $user = \App\Models\User::factory()->create();

        try {
            $this->actingAs($user)
                ->get(route('account.index'))
                ->assertOk()
                ->assertSee('cabinet-new', false)
                ->assertSee('theme-cabinet-new.css');
        } finally {
            config()->set('cabinet_design.default', 'legacy');
            Cache::forget(config('cabinet_design.cache_key'));
        }
    }
}
