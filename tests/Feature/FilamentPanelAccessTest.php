<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
    }

    public function test_admin_can_open_admin_panel(): void
    {
        $user = $this->createUserWithPermissions(['browse_admin']);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_user_without_admin_permission_is_forbidden(): void
    {
        $user = $this->createUserWithPermissions([]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_filament_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_panel_routes_are_hidden_when_feature_flag_is_disabled(): void
    {
        config()->set('admin.filament_enabled', false);

        $this->getJson('/admin')->assertNotFound();
        $this->getJson('/admin/login')->assertNotFound();
    }

    public function test_old_control_path_and_legacy_admin_routes_are_not_registered(): void
    {
        $this->getJson('/control')->assertNotFound();

        $this->assertFalse(app('router')->has('voyager.dashboard'));
        $this->assertFalse(app('router')->has('voyager.media.index'));
        $this->assertFalse(app('router')->has('admin.activity-log'));
        $this->assertFalse(app('router')->has('admin.category-tree'));
    }

    private function createUserWithPermissions(array $permissionKeys): User
    {
        $role = Role::query()->create([
            'name' => 'role-'.uniqid(),
            'display_name' => 'Test role',
        ]);

        foreach ($permissionKeys as $key) {
            $permission = Permission::query()->create([
                'key' => $key,
                'table_name' => null,
            ]);

            $role->permissions()->attach($permission);
        }

        return User::factory()->create(['role_id' => $role->id]);
    }
}
