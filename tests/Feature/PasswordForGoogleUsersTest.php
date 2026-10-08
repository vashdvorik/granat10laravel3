<?php

namespace Tests\Feature;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\FormProtectionFields;
use Tests\TestCase;

/** Пользователь, пришедший через Google, не знает пароль: не требуем его и даём возможность задать свой. */
class PasswordForGoogleUsersTest extends TestCase
{
    use RefreshDatabase, FormProtectionFields;

    private function googleUser(): User
    {
        $user = User::factory()->create(['email' => 'google.user@example.com', 'password' => 'unknown-random-secret']);
        $user->forceFill(['password_set_at' => null])->save();

        return $user;
    }

    public function test_google_user_sees_set_password_form_without_current_password_field(): void
    {
        $this->actingAs($this->googleUser())->get(route('account.user.change_password'))
            ->assertOk()
            ->assertSee('Задать пароль')
            ->assertDontSee('name="current_password"', false)
            ->assertSee('google.user@example.com');
    }

    public function test_google_user_can_set_password_and_then_log_in_with_email_and_password(): void
    {
        $user = $this->googleUser();

        $this->actingAs($user)->post(route('account.user.update_password'), [
            'password' => 'My-New-Passw0rd',
            'password_confirmation' => 'My-New-Passw0rd',
        ])->assertRedirect(route('account.user.index'))->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->hasPassword());
        $this->assertTrue(Hash::check('My-New-Passw0rd', $user->password));

        auth()->logout();
        $this->post('/login', ['email' => 'google.user@example.com', 'password' => 'My-New-Passw0rd'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_confirmation_is_still_required_when_setting_first_password(): void
    {
        $this->actingAs($this->googleUser())->post(route('account.user.update_password'), [
            'password' => 'My-New-Passw0rd',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');
    }

    public function test_user_with_password_must_still_enter_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password-1']);

        $this->actingAs($user)->get(route('account.user.change_password'))
            ->assertOk()->assertSee('name="current_password"', false)->assertSee('Смена пароля');

        $this->actingAs($user)->post(route('account.user.update_password'), [
            'password' => 'My-New-Passw0rd', 'password_confirmation' => 'My-New-Passw0rd',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->post(route('account.user.update_password'), [
            'current_password' => 'wrong', 'password' => 'My-New-Passw0rd', 'password_confirmation' => 'My-New-Passw0rd',
        ])->assertRedirect(route('account.user.change_password'))->assertSessionHas('error');

        $this->assertTrue(Hash::check('old-password-1', $user->fresh()->password));
    }

    public function test_google_user_deletes_shop_by_typing_its_name(): void
    {
        $user = $this->googleUser();
        $seller = Seller::factory()->create(['user_id' => $user->id, 'name' => 'Мой Магазин']);

        $this->actingAs($user)->get(route('account.store.delete'))
            ->assertOk()->assertDontSee('name="password"', false)->assertSee('name="confirmation"', false);

        $this->actingAs($user)->delete(route('account.store.destroy'), ['confirmation' => 'не тот магазин'])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('sellers', ['id' => $seller->id]);

        $this->actingAs($user)->delete(route('account.store.destroy'), ['confirmation' => '  мой магазин '])
            ->assertRedirect(route('account.index'));
        $this->assertDatabaseMissing('sellers', ['id' => $seller->id]);
    }

    public function test_user_with_password_deletes_shop_only_with_password(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);
        $seller = Seller::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete(route('account.store.destroy'), ['confirmation' => $seller->name])
            ->assertSessionHasErrors('password');
        $this->assertDatabaseHas('sellers', ['id' => $seller->id]);

        $this->actingAs($user)->delete(route('account.store.destroy'), ['password' => 'secret-pass-1'])
            ->assertRedirect(route('account.index'));
        $this->assertDatabaseMissing('sellers', ['id' => $seller->id]);
    }

    public function test_profile_offers_to_set_password_only_to_users_without_one(): void
    {
        $this->actingAs($this->googleUser())->get(route('account.user.index'))
            ->assertOk()->assertSee('Вы входите через Google')->assertSee('Задать пароль');

        $this->actingAs(User::factory()->create())->get(route('account.user.index'))
            ->assertOk()->assertDontSee('Вы входите через Google');
    }

    public function test_registration_and_password_reset_mark_password_as_set(): void
    {
        $this->post('/register', $this->formProtection() + [
            'name' => 'Иван', 'email' => 'reg@example.com',
            'password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);
        $this->assertTrue(User::query()->where('email', 'reg@example.com')->firstOrFail()->hasPassword());

        $user = $this->googleUser();
        app(ResetUserPassword::class)->reset($user, ['password' => 'Str0ng-Passw0rd!', 'password_confirmation' => 'Str0ng-Passw0rd!']);
        $this->assertTrue($user->fresh()->hasPassword());
    }
}
