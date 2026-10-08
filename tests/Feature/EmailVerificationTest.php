<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailCustom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\FormProtectionFields;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase, FormProtectionFields;

    private function unverifiedUser(array $attributes = []): User
    {
        return User::factory()->unverified()->create($attributes);
    }

    private function verificationUrl(User $user, int $minutes = 60, ?string $hash = null): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), [
            'id' => $user->getKey(),
            'hash' => $hash ?? sha1($user->getEmailForVerification()),
        ]);
    }

    public function test_registration_creates_unverified_user_and_sends_verification_email(): void
    {
        Notification::fake();

        $response = $this->post('/register', $this->formProtection() + [
            'name' => 'Иван Петров',
            'email' => 'ivan@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'ivan@example.com')->firstOrFail();
        $this->assertNull($user->phone);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmailCustom::class);
    }

    public function test_registration_form_asks_only_for_data_that_google_provides(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        foreach (['name="name"', 'name="email"', 'name="password"', 'name="password_confirmation"'] as $field) {
            $this->assertStringContainsString($field, $html);
        }
        $this->assertStringNotContainsString('name="phone"', $html);
    }

    public function test_unverified_user_is_sent_to_verification_page_instead_of_cabinet(): void
    {
        $user = $this->unverifiedUser();

        foreach (['account.index', 'account.user.index', 'account.store.products.index', 'account.user.balance.topup'] as $route) {
            $this->actingAs($user)->get(route($route))->assertRedirect(route('verification.notice'));
        }
    }

    public function test_verification_page_shows_address_and_resend_button(): void
    {
        $user = $this->unverifiedUser(['email' => 'wait@example.com']);

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('wait@example.com')
            ->assertSee('Отправить письмо ещё раз');
    }

    public function test_verified_user_opens_cabinet_and_skips_verification_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('account.index'))->assertOk();
        $this->actingAs($user)->get(route('verification.notice'))->assertRedirect();
    }

    public function test_link_from_email_verifies_address_and_opens_cabinet(): void
    {
        $user = $this->unverifiedUser();

        $this->actingAs($user)->get($this->verificationUrl($user))
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('success');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->actingAs($user->fresh())->get(route('account.index'))->assertOk();
    }

    public function test_link_with_wrong_hash_or_expired_signature_is_rejected(): void
    {
        $user = $this->unverifiedUser();

        $this->actingAs($user)->get($this->verificationUrl($user, 60, sha1('other@example.com')))->assertForbidden();
        $this->actingAs($user)->get($this->verificationUrl($user, -5))->assertForbidden();
        $this->actingAs($user)->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_link_of_another_user_does_not_verify_current_user(): void
    {
        $victim = $this->unverifiedUser();
        $attacker = $this->unverifiedUser();

        $this->actingAs($attacker)->get($this->verificationUrl($victim))->assertForbidden();

        $this->assertNull($victim->fresh()->email_verified_at);
        $this->assertNull($attacker->fresh()->email_verified_at);
    }

    public function test_user_can_request_new_verification_email(): void
    {
        Notification::fake();
        $user = $this->unverifiedUser();

        $this->actingAs($user)->post(route('verification.send'))
            ->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmailCustom::class);
    }

    public function test_verification_mail_is_in_russian_and_contains_signed_link(): void
    {
        $user = $this->unverifiedUser();

        $mail = (new VerifyEmailCustom)->toMail($user);

        $this->assertSame('Подтвердите адрес электронной почты', $mail->subject);
        $this->assertStringContainsString('/email/verify/'.$user->id.'/', $mail->actionUrl);
        $this->assertStringContainsString('signature=', $mail->actionUrl);
        $this->assertSame('Подтвердить почту', $mail->actionText);
    }

    public function test_changing_email_in_profile_requires_new_confirmation(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($user)->put(route('account.user.update'), [
            'name' => $user->name,
            'email' => 'new@example.com',
            'phone' => '77712345',
        ])->assertRedirect(route('verification.notice'));

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmailCustom::class);
    }

    public function test_profile_update_without_email_change_keeps_address_verified(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'same@example.com']);

        $this->actingAs($user)->put(route('account.user.update'), [
            'name' => 'Новое имя',
            'email' => 'SAME@example.com',
            'phone' => '77712345',
        ])->assertRedirect(route('account.user.index'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_migration_marks_existing_users_as_verified(): void
    {
        $old = $this->unverifiedUser();
        DB::table('users')->where('id', $old->id)->update(['email_verified_at' => null]);

        $migration = require database_path('migrations/2026_10_08_000001_verify_existing_users_email.php');
        $migration->up();

        $this->assertNotNull($old->fresh()->email_verified_at);
    }
}
