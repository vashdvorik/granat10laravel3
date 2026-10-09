<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** После входа по логину и паролю продавец попадает в кабинет, а не на публичный сайт. */
class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Seller-Passw0rd!';

    private function seller(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'email' => 'seller@example.com',
            'password' => bcrypt(self::PASSWORD),
        ], $overrides));
        Seller::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_seller_lands_in_the_cabinet_after_login(): void
    {
        $this->seller();

        $this->post('/login', ['email' => 'seller@example.com', 'password' => self::PASSWORD])
            ->assertRedirect(route('account.index'));

        $this->assertAuthenticated();
        $this->get(route('account.index'))->assertOk();
    }

    public function test_login_from_the_public_site_still_leads_to_the_cabinet(): void
    {
        $this->seller();

        // продавец нажал «Войти» на витрине: страница, с которой он пришёл, не должна возвращать его на витрину
        $this->from(route('home'))
            ->post('/login', ['email' => 'seller@example.com', 'password' => self::PASSWORD])
            ->assertRedirect(route('account.index'));
    }

    public function test_guest_who_opened_a_cabinet_page_returns_to_it_after_login(): void
    {
        $this->seller();

        $this->get(route('account.vip.index'))->assertRedirect(route('login'));

        $this->post('/login', ['email' => 'seller@example.com', 'password' => self::PASSWORD])
            ->assertRedirect(route('account.vip.index'));
    }

    public function test_user_with_unconfirmed_email_is_sent_to_confirm_it_on_the_way_to_the_cabinet(): void
    {
        $this->seller(['email_verified_at' => null]);

        $this->post('/login', ['email' => 'seller@example.com', 'password' => self::PASSWORD])
            ->assertRedirect(route('account.index'));

        $this->get(route('account.index'))->assertRedirect(route('verification.notice'));
    }

    public function test_wrong_password_does_not_log_in_and_stays_on_the_login_page(): void
    {
        $this->seller();

        $this->from(route('login'))
            ->post('/login', ['email' => 'seller@example.com', 'password' => 'wrong-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logged_in_seller_opening_the_login_page_goes_to_the_cabinet(): void
    {
        $this->actingAs($this->seller())
            ->get(route('login'))
            ->assertRedirect(route('account.index'));
    }

    public function test_fortify_home_points_to_the_cabinet(): void
    {
        $this->assertSame('/account', config('fortify.home'));
    }
}
