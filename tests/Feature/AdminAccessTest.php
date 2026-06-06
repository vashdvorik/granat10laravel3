<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use TCG\Voyager\Models\Permission;
use TCG\Voyager\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'admin.access'])
            ->get('/_test/admin-access', fn () => response('admin-ok'));
    }

    public function test_user_with_browse_admin_permission_can_access_admin_route(): void
    {
        $user = $this->createUserWithPermissions(['browse_admin']);

        $this->actingAs($user)
            ->get('/_test/admin-access')
            ->assertOk()
            ->assertSee('admin-ok');
    }

    public function test_user_without_browse_admin_permission_is_forbidden(): void
    {
        $user = $this->createUserWithPermissions([]);

        $this->actingAs($user)
            ->get('/_test/admin-access')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/_test/admin-access')
            ->assertRedirect('/login');
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
