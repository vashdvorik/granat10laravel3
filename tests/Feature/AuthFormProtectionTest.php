<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\FormProtectionFields;
use Tests\TestCase;

class AuthFormProtectionTest extends TestCase
{
    use RefreshDatabase, FormProtectionFields;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        RateLimiter::clear('x');
    }

    private function registration(array $override = [], ?array $protection = null): array
    {
        return ($protection ?? $this->formProtection()) + $override + [
            'name' => 'Иван Петров',
            'email' => 'ivan@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ];
    }

    public function test_registration_forms_carry_honeypot_and_token(): void
    {
        foreach (['register', 'password.request'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertSee('name="contact_url"', false)
                ->assertSee('name="form_token"', false);
        }
    }

    public function test_normal_registration_passes_all_checks(): void
    {
        $this->post('/register', $this->registration())->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', ['email' => 'ivan@example.com']);
    }

    public function test_honeypot_filled_by_bot_blocks_registration(): void
    {
        $this->from('/register')->post('/register', $this->registration([], ['contact_url' => 'http://spam.example'] + $this->formProtection()))
            ->assertRedirect('/register')->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'ivan@example.com']);
    }

    public function test_form_without_token_or_submitted_too_fast_is_rejected(): void
    {
        $this->post('/register', $this->registration([], ['contact_url' => '']))->assertSessionHasErrors('email');
        $this->post('/register', $this->registration([], $this->formProtection(0)))->assertSessionHasErrors('email');
        $this->post('/register', $this->registration([], ['contact_url' => '', 'form_token' => 'garbage']))->assertSessionHasErrors('email');

        $this->assertSame(0, User::query()->count());
    }

    public function test_stale_form_is_rejected(): void
    {
        $this->post('/register', $this->registration([], $this->formProtection(4 * 3600)))->assertSessionHasErrors('email');

        $this->assertSame(0, User::query()->count());
    }

    public function test_registration_is_rate_limited_per_ip(): void
    {
        config(['auth_protection.register.per_minute' => 3]);

        for ($i = 1; $i <= 3; $i++) {
            $this->post('/register', $this->registration(['email' => "user{$i}@example.com"]));
            auth()->logout();
        }

        $this->post('/register', $this->registration(['email' => 'user4@example.com']))
            ->assertSessionHasErrors('email');

        $this->assertSame(3, User::query()->count());
        $this->assertStringContainsString('Слишком много попыток', session('errors')->first('email'));
    }

    public function test_failed_attempts_also_count_towards_limit(): void
    {
        config(['auth_protection.register.per_minute' => 2]);

        $this->post('/register', $this->registration([], ['contact_url' => 'spam'] + $this->formProtection()));
        $this->post('/register', $this->registration([], ['contact_url' => 'spam'] + $this->formProtection()));
        $this->post('/register', $this->registration())->assertSessionHasErrors('email');

        $this->assertSame(0, User::query()->count());
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        config(['auth_protection.forgot_password.per_minute' => 2]);
        $payload = $this->formProtection() + ['email' => 'nobody@example.com'];

        $this->post('/forgot-password', $payload);
        $this->post('/forgot-password', $this->formProtection() + ['email' => 'nobody@example.com']);

        $this->post('/forgot-password', $this->formProtection() + ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Слишком много попыток', session('errors')->first('email'));
    }

    public function test_disposable_email_domains_are_not_accepted(): void
    {
        $this->post('/register', $this->registration(['email' => 'bot@mailinator.com']))
            ->assertSessionHasErrors('email');
        $this->post('/register', $this->registration(['email' => 'bot@sub.yopmail.com']))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, User::query()->count());
    }

    public function test_turnstile_is_required_when_configured(): void
    {
        config(['auth_protection.turnstile.site_key' => 'site', 'auth_protection.turnstile.secret' => 'secret']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->get(route('register'))->assertSee('cf-turnstile', false);

        // без ответа капчи
        $this->post('/register', $this->registration())->assertSessionHasErrors('email');
        $this->assertSame(0, User::query()->count());

        // с успешной проверкой
        $this->post('/register', $this->registration(['cf-turnstile-response' => 'token-ok']))
            ->assertRedirect(route('verification.notice'));
        $this->assertSame(1, User::query()->count());
    }

    public function test_turnstile_failure_blocks_and_outage_does_not(): void
    {
        config(['auth_protection.turnstile.site_key' => 'site', 'auth_protection.turnstile.secret' => 'secret']);

        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);
        $this->post('/register', $this->registration(['cf-turnstile-response' => 'bad']))->assertSessionHasErrors('email');
        $this->assertSame(0, User::query()->count());

        // Cloudflare недоступен: остальные защиты работают, регистрация не блокируется чужим сбоем
        Http::fake(['challenges.cloudflare.com/*' => fn () => throw new \RuntimeException('timeout')]);
        $this->post('/register', $this->registration(['cf-turnstile-response' => 'whatever']))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_turnstile_widget_is_hidden_without_keys(): void
    {
        $this->get(route('register'))->assertDontSee('cf-turnstile', false);
    }

    public function test_mail_outage_does_not_break_registration_or_resend(): void
    {
        config([
            'mail.default' => 'broken',
            'mail.mailers.broken' => ['transport' => 'smtp', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 1],
        ]);

        $this->post('/register', $this->registration())
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-failed');

        $user = User::query()->where('email', 'ivan@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);

        $this->actingAs($user)->post(route('verification.send'))
            ->assertSessionHas('status', 'verification-link-failed');

        $this->actingAs($user)->get(route('verification.notice'))->assertOk();
    }
}
