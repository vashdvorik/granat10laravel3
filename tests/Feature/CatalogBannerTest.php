<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\SiteSetting;
use App\Services\CatalogBannerManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_banner_is_shared_by_catalog_category_and_not_found_pages(): void
    {
        Storage::fake('public');
        $path = 'site/banners/shared-catalog-banner.jpg';
        Storage::disk('public')->put($path, 'banner');
        app(CatalogBannerManager::class)->setBanners([[
            'image' => $path,
            'link' => '/sale',
        ]]);

        $bannerUrl = Storage::disk('public')->url($path);
        $category = Category::factory()->create(['is_active' => true]);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee($bannerUrl, false)
            ->assertSee('href="/sale"', false)
            ->assertSee('class="image-bd effect_img2"', false);

        $this->get(route('catalog.show', $category->alias))
            ->assertOk()
            ->assertSee($bannerUrl, false)
            ->assertSee('href="/sale"', false)
            ->assertSee('class="image-bd effect_img2"', false);

        $this->get('/page-that-does-not-exist-for-banner-test')
            ->assertNotFound()
            ->assertSee($bannerUrl, false)
            ->assertSee('href="/sale"', false)
            ->assertSee('class="image-bd effect_img2"', false);
    }

    public function test_default_banner_remains_available_without_a_filament_setting(): void
    {
        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee(asset('img/shop-banner.jpg'), false)
            ->assertSee('<a class="image-bd effect_img2" ><img', false);
    }

    public function test_random_selector_can_choose_a_banner_other_than_the_first_one(): void
    {
        Storage::fake('public');
        $firstPath = 'site/banners/first.jpg';
        $secondPath = 'site/banners/second.jpg';
        Storage::disk('public')->put($firstPath, 'first');
        Storage::disk('public')->put($secondPath, 'second');

        $manager = new class extends CatalogBannerManager
        {
            protected function randomIndex(int $maximum): int
            {
                return $maximum;
            }
        };

        $manager->setBanners([
            ['image' => $firstPath, 'link' => '/first'],
            ['image' => $secondPath, 'link' => '/second'],
        ]);

        $this->assertSame([
            'url' => Storage::disk('public')->url($secondPath),
            'link' => '/second',
        ], $manager->forDisplay());
    }

    public function test_existing_single_banner_is_loaded_as_a_banner_list(): void
    {
        SiteSetting::query()->create([
            'key' => config('catalog_banner.legacy_image_setting_key'),
            'value' => 'site/banners/legacy.jpg',
        ]);
        SiteSetting::query()->create([
            'key' => config('catalog_banner.legacy_link_setting_key'),
            'value' => '/legacy',
        ]);

        $this->assertSame([[
            'image' => 'site/banners/legacy.jpg',
            'link' => '/legacy',
        ]], app(CatalogBannerManager::class)->all());
    }
}
