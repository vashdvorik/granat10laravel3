<?php

namespace Tests\Feature;

use App\Enums\BalanceTransactionType;
use App\Models\BalanceTopup;
use App\Models\BalanceTransaction;
use App\Models\Role;
use App\Models\User;
use App\Services\BalanceAdjustmentService;
use App\Services\BalanceLedger;
use App\Services\DeveloperTipService;
use App\Services\WebPayment\BalanceTopupService;
use App\Services\WebPayment\WebPaymentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

/** Единый журнал баланса: запись операций и страница «Баланс и операции». */
class BalanceJournalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function entry(User $user, int $amount, BalanceTransactionType $type = BalanceTransactionType::Topup, string $description = 'Тест', ?\DateTimeInterface $at = null): BalanceTransaction
    {
        $row = app(BalanceLedger::class)->record($user, $type, $amount, 10000, $description);

        if ($at !== null) {
            $row->forceFill(['created_at' => $at])->save();
        }

        return $row;
    }

    // ------------------------------------------------------------------ запись операций

    public function test_paid_topup_is_written_to_journal(): void
    {
        config(['services.webpayment.enabled' => true, 'services.webpayment.merchant_login' => 'x', 'services.webpayment.merchant_pass' => 'y',
            'services.webpayment.currency' => '000', 'services.webpayment.is_test' => false]);
        $user = User::factory()->create(['balance' => 10]);
        $topup = BalanceTopup::query()->create([
            'user_id' => $user->id, 'amount_cents' => 25000, 'currency' => '000', 'status' => 'pending',
            'is_test' => false, 'expires_at' => now()->addMinutes(30),
        ]);
        $this->partialMock(WebPaymentClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getState')->andReturn(['state' => 1, 'sum' => 25000, 'currency' => '000', 'istest' => 0, 'rrn' => '77', 'lastdgt' => '4242']);
        });

        app(BalanceTopupService::class)->settle($topup);

        $row = BalanceTransaction::query()->firstOrFail();
        $this->assertSame(BalanceTransactionType::Topup, $row->type);
        $this->assertSame(25000, $row->amount_cents);
        $this->assertSame(26000, $row->balance_after_cents);
        $this->assertSame($topup->id, $row->reference_id);
        $this->assertSame('77', $row->meta['rrn']);
    }

    public function test_tip_is_written_for_both_sides(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $admin = User::factory()->create(['name' => 'Админ']);
        $admin->forceFill(['role_id' => $role->id, 'balance' => '5.00'])->save();
        $user = User::factory()->create(['name' => 'Продавец', 'balance' => 100]);

        app(DeveloperTipService::class)->send($user, '40');

        $sent = BalanceTransaction::query()->where('type', 'tip_sent')->firstOrFail();
        $this->assertSame($user->id, $sent->user_id);
        $this->assertSame(-4000, $sent->amount_cents);
        $this->assertSame(6000, $sent->balance_after_cents);

        $received = BalanceTransaction::query()->where('type', 'tip_received')->firstOrFail();
        $this->assertSame($admin->id, $received->user_id);
        $this->assertSame(4000, $received->amount_cents);
        $this->assertSame(4500, $received->balance_after_cents);
        $this->assertStringContainsString('Продавец', $received->description);
    }

    public function test_admin_adjustment_is_written_with_reason(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin']);
        $admin = User::factory()->create();
        $admin->forceFill(['role_id' => $role->id])->save();
        $this->seedBrowseAdminPermission($admin);
        $target = User::factory()->create(['balance' => 10]);

        app(BalanceAdjustmentService::class)->adjust($admin, $target, '-3.50', 'Ошибочное начисление');

        $row = BalanceTransaction::query()->where('user_id', $target->id)->firstOrFail();
        $this->assertSame(BalanceTransactionType::AdminAdjustment, $row->type);
        $this->assertSame(-350, $row->amount_cents);
        $this->assertSame(650, $row->balance_after_cents);
        $this->assertStringContainsString('Ошибочное начисление', $row->description);
    }

    private function seedBrowseAdminPermission(User $admin): void
    {
        $permission = \App\Models\Permission::query()->firstOrCreate(['key' => 'browse_admin']);
        $admin->role->permissions()->syncWithoutDetaching([$permission->id]);
        $admin->unsetRelation('role');
    }

    // ------------------------------------------------------------------ страница журнала

    public function test_journal_shows_only_own_operations_with_signs_and_balance(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $other = User::factory()->create();
        $this->entry($user, 50000, BalanceTransactionType::Topup, 'Пополнение картой, счёт №7');
        $this->entry($user, -3000, BalanceTransactionType::VipPurchase, 'VIP для товара «Чайник»');
        $this->entry($other, 99900, BalanceTransactionType::Topup, 'Чужое пополнение');

        $this->actingAs($user)->get(route('account.balance.index'))
            ->assertOk()
            ->assertSee('Баланс и операции')
            ->assertSee('Пополнение картой, счёт №7')
            ->assertSee('VIP для товара «Чайник»')
            ->assertSee('+500.00 ₽')
            ->assertSee('−30.00 ₽')
            ->assertSee('+500,00 ₽')   // поступило за период
            ->assertSee('−30,00 ₽')    // потрачено за период
            ->assertDontSee('Чужое пополнение');
    }

    public function test_journal_filters_by_type_and_period(): void
    {
        $user = User::factory()->create();
        $this->entry($user, 1000, BalanceTransactionType::Topup, 'Свежее пополнение');
        $this->entry($user, -500, BalanceTransactionType::VipPurchase, 'Свежий VIP');
        $this->entry($user, 2000, BalanceTransactionType::Topup, 'Старое пополнение', now()->subDays(200));

        $this->actingAs($user)->get(route('account.balance.index', ['type' => 'topup']))
            ->assertOk()->assertSee('Свежее пополнение')->assertDontSee('Свежий VIP');

        $this->actingAs($user)->get(route('account.balance.index', ['period' => '30']))
            ->assertOk()->assertSee('Свежий VIP')->assertDontSee('Старое пополнение');

        $this->actingAs($user)->get(route('account.balance.index', ['type' => 'hacked', 'period' => 'evil']))
            ->assertOk()->assertSee('Старое пополнение');
    }

    public function test_journal_empty_state_and_pending_invoices(): void
    {
        $user = User::factory()->create();
        BalanceTopup::query()->create([
            'user_id' => $user->id, 'amount_cents' => 15000, 'currency' => '000', 'status' => 'pending',
            'is_test' => false, 'expires_at' => now()->addMinutes(20),
        ]);

        $this->actingAs($user)->get(route('account.balance.index'))
            ->assertOk()
            ->assertSee('Операций пока нет')
            ->assertSee('Есть неоплаченные счета')
            ->assertSee('150,00 ₽');
    }

    public function test_journal_is_paginated(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 25) as $i) {
            $this->entry($user, 100, BalanceTransactionType::Topup, "Операция номер {$i}");
        }

        $this->actingAs($user)->get(route('account.balance.index'))
            ->assertOk()->assertSee('Операция номер 25')->assertDontSee('Операция номер 1<', false);
        $this->actingAs($user)->get(route('account.balance.index', ['page' => 2]))
            ->assertOk()->assertSee('Операция номер 1');
    }

    public function test_journal_exports_csv_for_excel(): void
    {
        $user = User::factory()->create();
        $this->entry($user, 12345, BalanceTransactionType::Topup, 'Пополнение для CSV');
        $this->entry(User::factory()->create(), 999, BalanceTransactionType::Topup, 'Чужое');

        $response = $this->actingAs($user)->get(route('account.balance.index', ['export' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $body = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('Пополнение для CSV', $body);
        $this->assertStringContainsString('123,45', $body);
        $this->assertStringNotContainsString('Чужое', $body);
    }

    public function test_journal_requires_login_and_is_linked_from_profile(): void
    {
        $this->get(route('account.balance.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())->get(route('account.user.index'))
            ->assertOk()->assertSee(route('account.balance.index'), false);
    }
}
