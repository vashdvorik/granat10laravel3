<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\DesignSettings;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Дизайн сайта');
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
