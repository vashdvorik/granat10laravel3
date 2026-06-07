<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AdminRoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_admin_role_without_hardcoded_user(): void
    {
        Config::set('admin.seed_email');
        Config::set('admin.seed_password');

        $this->seed(AdminRoleSeeder::class);

        $role = Role::query()->where('name', 'admin')->firstOrFail();
        $permission = Permission::query()->where('key', 'browse_admin')->firstOrFail();

        $this->assertDatabaseHas('permission_role', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeder_promotes_configured_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'configured-admin@example.com']);
        Config::set('admin.seed_email', $user->email);

        $this->seed(AdminRoleSeeder::class);

        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();

        $this->assertSame($adminRole->id, $user->refresh()->role_id);
    }
}
