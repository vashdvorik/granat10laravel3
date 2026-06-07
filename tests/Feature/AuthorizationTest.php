<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use TCG\Voyager\Models\Permission as VoyagerPermission;
use TCG\Voyager\Models\Role as VoyagerRole;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_uses_standard_laravel_authenticatable(): void
    {
        $this->assertSame(Authenticatable::class, get_parent_class(User::class));
    }

    public function test_permission_is_loaded_from_primary_role(): void
    {
        $role = $this->createRoleWithPermission('browse_admin');
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($user->hasPermission('browse_admin'));
        $this->assertTrue(Gate::forUser($user)->allows('access-admin'));
    }

    public function test_permission_is_loaded_from_additional_role(): void
    {
        $role = $this->createRoleWithPermission('browse_admin');
        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->fresh()->hasPermission('browse_admin'));
        $this->assertTrue(Gate::forUser($user->fresh())->allows('access-admin'));
    }

    public function test_user_without_permission_is_denied_by_admin_gate(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasPermission('browse_admin'));
        $this->assertFalse(Gate::forUser($user)->allows('access-admin'));
    }

    public function test_existing_voyager_role_data_remains_compatible(): void
    {
        $role = VoyagerRole::query()->create([
            'name' => 'legacy-admin',
            'display_name' => 'Legacy administrator',
        ]);
        $permission = VoyagerPermission::query()->create([
            'key' => 'browse_admin',
            'table_name' => null,
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue(Gate::forUser($user->fresh())->allows('access-admin'));
        $this->assertInstanceOf(Role::class, $user->fresh()->role);
    }

    private function createRoleWithPermission(string $permissionKey): Role
    {
        $role = Role::query()->create([
            'name' => 'role-'.uniqid(),
            'display_name' => 'Test role',
        ]);
        $permission = Permission::query()->create([
            'key' => $permissionKey,
            'table_name' => null,
        ]);
        $role->permissions()->attach($permission);

        return $role;
    }
}
