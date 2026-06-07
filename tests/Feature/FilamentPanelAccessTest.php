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

    public function test_admin_can_open_control_panel(): void
    {
        $user = $this->createUserWithPermissions(['browse_admin']);

        $this->actingAs($user)
            ->get('/control')
            ->assertOk();
    }

    public function test_user_without_admin_permission_is_forbidden(): void
    {
        $user = $this->createUserWithPermissions([]);

        $this->actingAs($user)
            ->get('/control')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_filament_login(): void
    {
        $this->get('/control')
            ->assertRedirect('/control/login');
    }

    public function test_panel_routes_are_hidden_when_feature_flag_is_disabled(): void
    {
        config()->set('admin.filament_enabled', false);

        $this->get('/control')->assertNotFound();
        $this->get('/control/login')->assertNotFound();
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
