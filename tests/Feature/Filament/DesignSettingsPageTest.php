<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\CabinetDesignSettings;
use App\Filament\Pages\DesignSettings;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DesignSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
    }

    public function test_administrator_can_open_design_settings(): void
    {
        $this->actingAs($this->createAdministrator())
            ->get('/admin/design-settings')
            ->assertOk()
            ->assertSee('Дизайн сайта')
            ->assertSee('1398 × 316 px');
    }

    public function test_regular_user_cannot_open_design_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/design-settings')
            ->assertForbidden();
    }

    public function test_administrator_can_enable_new_design_globally(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(DesignSettings::class)
            ->fillForm(['theme' => 'new'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Дизайн сайта изменён');

        $this->assertSame(
            'new',
            SiteSetting::query()->where('key', config('design.setting_key'))->value('value')
        );
    }

    public function test_administrator_can_upload_multiple_catalog_banners_without_exact_dimension_restriction(): void
    {
        Storage::fake('public');
        $this->actingAs($this->createAdministrator());

        Livewire::test(DesignSettings::class)
            ->fillForm([
                'theme' => 'legacy',
                'catalog_banners' => [
                    [
                        'image' => [UploadedFile::fake()->image('catalog-banner-1.jpg', 1000, 200)],
                        'link' => '/sale',
                    ],
                    [
                        'image' => [UploadedFile::fake()->image('catalog-banner-2.jpg', 1200, 280)],
                        'link' => 'https://example.com/page',
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $stored = SiteSetting::query()
            ->where('key', config('catalog_banner.setting_key'))
            ->value('value');
        $banners = json_decode((string) $stored, true, flags: JSON_THROW_ON_ERROR);

        $this->assertCount(2, $banners);
        $this->assertSame('/sale', $banners[0]['link']);
        $this->assertSame('https://example.com/page', $banners[1]['link']);
        Storage::disk('public')->assertExists($banners[0]['image']);
        Storage::disk('public')->assertExists($banners[1]['image']);
    }

    public function test_catalog_banner_rejects_an_unsafe_link(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(DesignSettings::class)
            ->fillForm([
                'theme' => 'legacy',
                'catalog_banners' => [[
                    'image' => [UploadedFile::fake()->image('unsafe-link-banner.jpg')],
                    'link' => 'javascript:alert(1)',
                ]],
            ])
            ->call('save')
            ->assertHasFormErrors(['catalog_banners.0.link']);
    }

    public function test_administrator_can_enable_new_cabinet_design(): void
    {
        $this->actingAs($this->createAdministrator());

        Livewire::test(CabinetDesignSettings::class)
            ->fillForm(['theme' => 'new'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Дизайн кабинета изменён');

        $this->assertSame(
            'new',
            SiteSetting::query()->where('key', config('cabinet_design.setting_key'))->value('value')
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
}
