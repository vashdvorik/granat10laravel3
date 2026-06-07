<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\BalanceAdjustmentService;
use App\Services\UserRoleService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class UserAdministrationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_adjustment_is_atomic_and_logged(): void
    {
        $administrator = $this->createAdministrator();
        $user = User::factory()->create(['balance' => 100]);

        app(BalanceAdjustmentService::class)->adjust(
            $administrator,
            $user,
            '-25.55',
            'Возврат ошибочного начисления',
        );

        $this->assertSame(74.45, (float) $user->refresh()->balance);

        $activity = Activity::query()
            ->where('log_name', 'user_balance')
            ->where('subject_id', $user->id)
            ->firstOrFail();

        $this->assertSame($administrator->id, $activity->causer_id);
        $this->assertSame('100.00', $activity->properties->get('old_balance'));
        $this->assertSame('74.45', $activity->properties->get('new_balance'));
        $this->assertSame('-25.55', $activity->properties->get('adjustment'));
        $this->assertSame('Возврат ошибочного начисления', $activity->properties->get('reason'));
    }

    public function test_balance_adjustment_requires_reason(): void
    {
        $administrator = $this->createAdministrator();
        $user = User::factory()->create(['balance' => 100]);

        try {
            app(BalanceAdjustmentService::class)->adjust($administrator, $user, 10, '  ');
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }

        $this->assertSame(100.0, (float) $user->refresh()->balance);
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_balance_cannot_become_negative(): void
    {
        $administrator = $this->createAdministrator();
        $user = User::factory()->create(['balance' => 10]);

        try {
            app(BalanceAdjustmentService::class)->adjust(
                $administrator,
                $user,
                '-10.01',
                'Проверка ограничения',
            );
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }

        $this->assertSame(10.0, (float) $user->refresh()->balance);
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_balance_cannot_exceed_database_decimal_limit(): void
    {
        $administrator = $this->createAdministrator();
        $user = User::factory()->create(['balance' => 10]);

        try {
            app(BalanceAdjustmentService::class)->adjust(
                $administrator,
                $user,
                '100000000',
                'Проверка верхнего ограничения',
            );
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }

        $this->assertSame(10.0, (float) $user->refresh()->balance);
        $this->assertDatabaseCount('activity_log', 0);
    }

    public function test_regular_user_cannot_adjust_balance(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create(['balance' => 10]);

        $this->expectException(AuthorizationException::class);

        app(BalanceAdjustmentService::class)->adjust($actor, $target, 5, 'Пополнение');
    }

    public function test_primary_role_assignment_is_logged(): void
    {
        $administrator = $this->createAdministrator();
        $user = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'seller',
            'display_name' => 'Продавец',
        ]);

        app(UserRoleService::class)->assignPrimaryRole($administrator, $user, $role);

        $this->assertSame($role->id, $user->refresh()->role_id);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'user_roles',
            'subject_id' => $user->id,
            'causer_id' => $administrator->id,
        ]);
    }

    public function test_last_administrator_cannot_replace_admin_role(): void
    {
        [$administrator] = $this->createAdministratorWithRole();
        $regularRole = Role::query()->create([
            'name' => 'customer',
            'display_name' => 'Покупатель',
        ]);

        $this->expectException(AuthorizationException::class);

        app(UserRoleService::class)->assignPrimaryRole(
            $administrator,
            $administrator,
            $regularRole,
        );
    }

    public function test_role_can_be_removed_when_another_administrator_exists(): void
    {
        [$administrator, $adminRole] = $this->createAdministratorWithRole();
        User::factory()->create(['role_id' => $adminRole->id]);

        app(UserRoleService::class)->removeRole($administrator, $administrator, $adminRole);

        $this->assertNull($administrator->refresh()->role_id);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'user_roles',
            'subject_id' => $administrator->id,
            'causer_id' => $administrator->id,
        ]);
    }

    private function createAdministrator(): User
    {
        return $this->createAdministratorWithRole()[0];
    }

    private function createAdministratorWithRole(): array
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

        return [
            User::factory()->create(['role_id' => $role->id]),
            $role,
        ];
    }
}
