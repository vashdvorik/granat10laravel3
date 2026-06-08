<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ResetPasswordCustom;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
    }

    public function test_administrator_can_open_user_resource(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_regular_user_cannot_open_user_resource(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_user_list_supports_search_and_role_filter(): void
    {
        $administrator = $this->createAdministrator();
        $sellerRole = Role::query()->create([
            'name' => 'seller',
            'display_name' => 'Продавец',
        ]);
        $seller = User::factory()->create([
            'name' => 'Иван Продавец',
            'email' => 'seller@example.test',
            'role_id' => $sellerRole->id,
        ]);
        $customer = User::factory()->create([
            'name' => 'Покупатель',
            'email' => 'customer@example.test',
        ]);

        $this->actingAs($administrator);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$administrator, $seller, $customer])
            ->searchTable('seller@example.test')
            ->assertCanSeeTableRecords([$seller])
            ->assertCanNotSeeTableRecords([$administrator, $customer])
            ->searchTable()
            ->filterTable('role', $sellerRole->id)
            ->assertCanSeeTableRecords([$seller])
            ->assertCanNotSeeTableRecords([$administrator, $customer]);
    }

    public function test_administrator_can_create_user_with_password(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Новый пользователь',
                'email' => 'new-user@example.test',
                'phone' => '+37360000000',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $user = User::query()->where('email', 'new-user@example.test')->firstOrFail();

        $this->assertSame('Новый пользователь', $user->name);
        $this->assertSame('+37360000000', $user->phone);
        $this->assertNull($user->role_id);
        $this->assertSame(0.0, (float) $user->balance);
    }

    public function test_administrator_cannot_create_user_with_line_breaks_in_email(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Unsafe user',
                'email' => "user@example.com\r\nBcc: attacker@example.com",
                'phone' => '+37360000000',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);

        $this->assertDatabaseMissing('users', ['name' => 'Unsafe user']);
    }

    public function test_regular_edit_does_not_expose_or_change_password_role_and_balance(): void
    {
        $administrator = $this->createAdministrator();
        $role = Role::query()->create([
            'name' => 'customer',
            'display_name' => 'Покупатель',
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'balance' => 125.50,
        ]);
        $originalPassword = $user->password;

        $this->actingAs($administrator);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertFormFieldDoesNotExist('balance')
            ->assertFormFieldDoesNotExist('role_id')
            ->assertFormFieldIsHidden('password')
            ->fillForm([
                'name' => 'Обновлённое имя',
                'email' => 'updated@example.test',
                'phone' => '+37361111111',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $user->refresh();

        $this->assertSame('Обновлённое имя', $user->name);
        $this->assertSame('updated@example.test', $user->email);
        $this->assertSame($role->id, $user->role_id);
        $this->assertSame(125.50, (float) $user->balance);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_password_reset_action_sends_custom_notification(): void
    {
        NotificationFacade::fake();

        $administrator = $this->createAdministrator();
        $user = User::factory()->create();

        $this->actingAs($administrator);

        Livewire::test(ListUsers::class)
            ->callTableAction('resetPassword', $user);

        NotificationFacade::assertSentTo($user, ResetPasswordCustom::class);
    }

    public function test_role_and_balance_actions_use_protected_services(): void
    {
        $administrator = $this->createAdministrator();
        $user = User::factory()->create(['balance' => 10]);
        $role = Role::query()->create([
            'name' => 'seller',
            'display_name' => 'Продавец',
        ]);

        $this->actingAs($administrator);

        Livewire::test(ListUsers::class)
            ->callTableAction('assignRole', $user, [
                'role_id' => $role->id,
            ])
            ->assertNotified();

        $this->assertSame($role->id, $user->refresh()->role_id);

        Livewire::test(ListUsers::class)
            ->callTableAction('adjustBalance', $user, [
                'amount' => '5.25',
                'reason' => 'Тестовое пополнение',
            ])
            ->assertNotified();

        $this->assertSame(15.25, (float) $user->refresh()->balance);

        Livewire::test(ListUsers::class)
            ->callTableAction('removeRole', $user, [
                'role_id' => $role->id,
            ])
            ->assertNotified();

        $this->assertNull($user->refresh()->role_id);
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
