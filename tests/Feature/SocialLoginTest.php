<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use RuntimeException;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
        ]);
    }

    private function googleUser(array $attributes = [], array $raw = []): SocialiteUser
    {
        $user = new SocialiteUser;
        $user->map($attributes + ['id' => 'g-100', 'name' => 'Иван Гугл', 'email' => 'ivan@gmail.com']);
        $user->setRaw($raw + ['email_verified' => true]);

        return $user;
    }

    private function callbackReturns(SocialiteUser $user): void
    {
        Socialite::shouldReceive('driver->redirectUrl->user')->andReturn($user);
    }

    public function test_buttons_are_hidden_until_provider_keys_are_configured(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get(route('login'))->assertOk()->assertDontSee('Продолжить через Google');
        $this->get(route('register'))->assertOk()->assertDontSee('Продолжить через Google');
        $this->get('/auth/google/redirect')->assertNotFound();
        $this->get('/auth/google/callback')->assertNotFound();
    }

    public function test_login_and_register_pages_offer_google_one_click(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('Продолжить через Google')
            ->assertSee(route('social.redirect', 'google'), false);
        $this->get(route('register'))->assertOk()
            ->assertSee('Продолжить через Google');
    }

    public function test_unknown_provider_is_not_found(): void
    {
        $this->get('/auth/facebook/redirect')->assertNotFound();
    }

    public function test_redirect_goes_to_provider(): void
    {
        Socialite::shouldReceive('driver->redirectUrl->redirect')
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        $this->get(route('social.redirect', 'google'))->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_new_user_is_registered_verified_and_logged_in_in_one_click(): void
    {
        Notification::fake();
        $this->callbackReturns($this->googleUser());

        $this->get(route('social.callback', 'google'))->assertRedirect(route('account.index'));

        $user = User::query()->where('email', 'ivan@gmail.com')->firstOrFail();
        $this->assertSame('Иван Гугл', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->phone);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'g-100']);
        Notification::assertNothingSent(); // письмо подтверждения не нужно: почту подтвердил Google

        $this->get(route('account.index'))->assertOk();
    }

    public function test_repeat_login_uses_linked_account_even_if_email_changed_at_provider(): void
    {
        $user = User::factory()->create(['email' => 'first@example.com']);
        SocialAccount::query()->create(['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'g-100', 'email' => 'first@example.com']);
        $this->callbackReturns($this->googleUser(['email' => 'renamed@gmail.com']));

        $this->get(route('social.callback', 'google'))->assertRedirect(route('account.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
    }

    public function test_existing_verified_user_is_linked_by_email(): void
    {
        $user = User::factory()->create(['email' => 'ivan@gmail.com']);
        $this->callbackReturns($this->googleUser());

        $this->get(route('social.callback', 'google'))->assertRedirect(route('account.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
        $this->assertDatabaseHas('social_accounts', ['user_id' => $user->id, 'provider_user_id' => 'g-100']);
    }

    public function test_unverified_account_squatting_an_email_loses_its_password(): void
    {
        // Злоумышленник «зарегистрировал» чужой адрес со своим паролем и не подтвердил его
        $squatted = User::factory()->unverified()->create(['email' => 'ivan@gmail.com', 'password' => 'attacker-secret']);
        $this->callbackReturns($this->googleUser());

        $this->get(route('social.callback', 'google'))->assertRedirect(route('account.index'));

        $squatted->refresh();
        $this->assertAuthenticatedAs($squatted);
        $this->assertNotNull($squatted->email_verified_at);
        $this->assertFalse(Hash::check('attacker-secret', $squatted->password));
    }

    public function test_provider_with_unconfirmed_email_is_rejected(): void
    {
        $this->callbackReturns($this->googleUser([], ['email_verified' => false]));

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_provider_without_email_is_rejected(): void
    {
        $this->callbackReturns($this->googleUser(['email' => null]));

        $this->get(route('social.callback', 'google'))->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_cancelled_authorization_returns_to_login_with_message(): void
    {
        $this->get(route('social.callback', ['provider' => 'google', 'error' => 'access_denied']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_provider_failure_does_not_crash(): void
    {
        Socialite::shouldReceive('driver->redirectUrl->user')->andThrow(new RuntimeException('invalid_grant'));

        $this->get(route('social.callback', 'google'))->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logged_in_user_cannot_use_social_routes(): void
    {
        $this->actingAs(User::factory()->create())->get(route('social.redirect', 'google'))->assertRedirect();
    }

    public function test_verification_email_is_not_required_for_cabinet_after_social_registration(): void
    {
        $this->callbackReturns($this->googleUser(['id' => 'g-200', 'email' => 'new@gmail.com']));

        $this->get(route('social.callback', 'google'));

        $this->get(route('account.user.index'))->assertOk()->assertSee('new@gmail.com');
    }
}
