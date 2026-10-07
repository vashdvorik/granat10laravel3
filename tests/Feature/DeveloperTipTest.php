<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DeveloperTipTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $role = Role::query()->firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['role_id' => $role->id, 'balance' => '10.00'])->save();
    }

    private function userWithBalance(string $balance): User
    {
        $user = User::factory()->create();
        $user->forceFill(['balance' => $balance])->save();

        return $user;
    }

    public function test_tip_moves_money_from_user_to_admin(): void
    {
        $user = $this->userWithBalance('300.00');

        $this->actingAs($user)->post(route('account.user.balance.tip'), ['amount' => '100,50'])
            ->assertRedirect(route('account.user.index'))
            ->assertSessionHas('success');

        $this->assertEquals(199.5, $user->fresh()->getRawOriginal('balance'));
        $this->assertEquals(110.5, $this->admin->fresh()->getRawOriginal('balance'));

        $log = Activity::query()->where('log_name', 'user_balance')->latest('id')->first();
        $this->assertSame('developer_tip', $log->getExtraProperty('source'));
        $this->assertSame($user->id, $log->causer_id);
    }

    public function test_tip_is_rejected_when_balance_is_insufficient(): void
    {
        $user = $this->userWithBalance('20.00');

        $this->actingAs($user)->post(route('account.user.balance.tip'), ['amount' => '50'])
            ->assertSessionHasErrors('amount');

        $this->assertEquals(20, $user->fresh()->getRawOriginal('balance'));
        $this->assertEquals(10, $this->admin->fresh()->getRawOriginal('balance'));
    }

    public function test_invalid_amounts_are_rejected(): void
    {
        $user = $this->userWithBalance('500.00');

        foreach (['0', '-5', 'abc', '99999999', '0,5'] as $amount) {
            $this->actingAs($user)->post(route('account.user.balance.tip'), ['amount' => $amount])
                ->assertSessionHasErrors('amount');
        }

        $this->assertEquals(500, $user->fresh()->getRawOriginal('balance'));
    }

    public function test_admin_cannot_tip_himself(): void
    {
        $this->actingAs($this->admin)->post(route('account.user.balance.tip'), ['amount' => '5'])
            ->assertSessionHasErrors('amount');

        $this->assertEquals(10, $this->admin->fresh()->getRawOriginal('balance'));
    }

    public function test_guest_cannot_tip(): void
    {
        $this->post(route('account.user.balance.tip'), ['amount' => '5'])->assertRedirect(route('login'));
    }

    public function test_configured_recipient_overrides_first_admin(): void
    {
        $other = $this->userWithBalance('0.00');
        $user = $this->userWithBalance('100.00');
        config(['developer_tip.recipient_user_id' => $other->id]);

        $this->actingAs($user)->post(route('account.user.balance.tip'), ['amount' => '30']);

        $this->assertEquals(30, $other->fresh()->getRawOriginal('balance'));
        $this->assertEquals(10, $this->admin->fresh()->getRawOriginal('balance'));
    }

    public function test_profile_page_shows_button_only_when_recipient_exists(): void
    {
        $user = $this->userWithBalance('100.00');

        $this->actingAs($user)->get(route('account.user.index'))
            ->assertOk()
            ->assertSee('Поблагодарить разработчика');

        User::query()->whereKey($this->admin->id)->update(['role_id' => null]);

        $this->actingAs($user)->get(route('account.user.index'))
            ->assertOk()
            ->assertDontSee('Поблагодарить разработчика');
    }
}
