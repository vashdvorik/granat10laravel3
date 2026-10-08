<?php

namespace Tests\Feature\Filament;

use App\Enums\BalanceTransactionType;
use App\Filament\Resources\BalanceTopupResource\Pages\ListBalanceTopups;
use App\Filament\Resources\BalanceTopupResource\Pages\ViewBalanceTopup;
use App\Filament\Resources\BalanceTransactionResource\Pages\ListBalanceTransactions;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\RelationManagers\BalanceTopupsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\BalanceTransactionsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\VipPurchasesRelationManager;
use App\Filament\Resources\VipPurchaseResource\Pages\ListVipPurchases;
use App\Filament\Widgets\FinanceOverviewWidget;
use App\Filament\Resources\BalanceTopupResource;
use App\Models\BalanceTopup;
use App\Models\BalanceTransaction;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\VipPurchase;
use App\Services\BalanceLedger;
use App\Services\WebPayment\WebPaymentClient;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

/** Раздел «Финансы» в админке: платежи, журнал баланса, покупки VIP, сводка. */
class FinanceResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('admin.filament_enabled', true);
        Filament::setCurrentPanel(Filament::getPanel('control'));
    }

    private function createAdministrator(): User
    {
        $role = Role::query()->create(['name' => 'admin-'.uniqid(), 'display_name' => 'Администратор']);
        $permission = Permission::query()->firstOrCreate(['key' => 'browse_admin'], ['table_name' => null]);
        $role->permissions()->attach($permission);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function topup(User $user, string $status = 'paid', int $cents = 25000, array $extra = []): BalanceTopup
    {
        return BalanceTopup::query()->create(array_merge([
            'user_id' => $user->id,
            'amount_cents' => $cents,
            'currency' => '000',
            'status' => $status,
            'is_test' => false,
            'expires_at' => now()->addMinutes(30),
            'paid_at' => $status === 'paid' ? now() : null,
        ], $extra));
    }

    private function configureBank(): void
    {
        config([
            'services.webpayment.enabled' => true,
            'services.webpayment.merchant_login' => 'x',
            'services.webpayment.merchant_pass' => 'y',
            'services.webpayment.currency' => '000',
            'services.webpayment.is_test' => false,
        ]);
    }

    // ------------------------------------------------------------------ доступ

    public function test_finance_pages_open_for_administrator_and_are_forbidden_for_others(): void
    {
        $administrator = $this->createAdministrator();
        $topup = $this->topup(User::factory()->create());
        $entry = app(BalanceLedger::class)->record($topup->user, BalanceTransactionType::Topup, 25000, 25000, 'Пополнение', $topup);
        $purchase = $this->purchase();

        foreach (['/admin/balance-topups', '/admin/balance-transactions', '/admin/vip-purchases',
            "/admin/balance-topups/{$topup->id}", "/admin/balance-transactions/{$entry->id}", "/admin/vip-purchases/{$purchase->id}"] as $url) {
            $this->actingAs($administrator)->get($url)->assertOk();
            $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        }
    }

    public function test_finance_records_are_read_only_even_for_administrator(): void
    {
        $administrator = $this->createAdministrator();
        $topup = $this->topup(User::factory()->create());

        $this->assertTrue(Gate::forUser($administrator)->allows('viewAny', BalanceTopup::class));
        $this->assertTrue(Gate::forUser($administrator)->allows('view', $topup));
        $this->assertFalse(Gate::forUser($administrator)->allows('update', $topup));
        $this->assertFalse(Gate::forUser($administrator)->allows('delete', $topup));
        $this->assertFalse(Gate::forUser($administrator)->allows('create', BalanceTransaction::class));
        $this->assertFalse(Gate::forUser($administrator)->allows('deleteAny', VipPurchase::class));
        $this->assertFalse(BalanceTopupResource::canCreate());
    }

    // ------------------------------------------------------------------ платежи

    public function test_payments_table_filters_by_status_mode_and_overdue(): void
    {
        $administrator = $this->createAdministrator();
        $user = User::factory()->create();
        $paid = $this->topup($user, 'paid');
        $failed = $this->topup($user, 'failed');
        $test = $this->topup($user, 'paid', 100, ['is_test' => true]);
        $overdue = $this->topup($user, 'pending', 500, ['expires_at' => now()->subHour()]);
        $fresh = $this->topup($user, 'pending', 600);

        $this->actingAs($administrator);

        Livewire::test(ListBalanceTopups::class)
            ->assertCanSeeTableRecords([$paid, $failed, $test, $overdue, $fresh])
            ->filterTable('status', ['failed'])
            ->assertCanSeeTableRecords([$failed])
            ->assertCanNotSeeTableRecords([$paid, $overdue])
            ->resetTableFilters()
            ->filterTable('is_test', true)
            ->assertCanSeeTableRecords([$test])
            ->assertCanNotSeeTableRecords([$paid, $failed])
            ->resetTableFilters()
            ->filterTable('overdue', true)
            ->assertCanSeeTableRecords([$overdue])
            ->assertCanNotSeeTableRecords([$fresh, $paid]);
    }

    public function test_payments_table_searches_by_user_email_and_rrn(): void
    {
        $administrator = $this->createAdministrator();
        $anna = User::factory()->create(['name' => 'Анна', 'email' => 'anna-pay@example.test']);
        $boris = User::factory()->create(['name' => 'Борис', 'email' => 'boris@example.test']);
        $annaTopup = $this->topup($anna, 'paid', 25000, ['rrn' => '555111']);
        $borisTopup = $this->topup($boris, 'paid', 25000, ['rrn' => '777222']);

        $this->actingAs($administrator);

        Livewire::test(ListBalanceTopups::class)
            ->searchTable('anna-pay@example.test')
            ->assertCanSeeTableRecords([$annaTopup])
            ->assertCanNotSeeTableRecords([$borisTopup])
            ->searchTable('777222')
            ->assertCanSeeTableRecords([$borisTopup])
            ->assertCanNotSeeTableRecords([$annaTopup]);
    }

    public function test_payment_page_shows_bank_data_and_ledger_entry(): void
    {
        $administrator = $this->createAdministrator();
        $user = User::factory()->create(['name' => 'Плательщик Пётр', 'email' => 'petr@example.test', 'balance' => 250]);
        $topup = $this->topup($user, 'paid', 25000, ['rrn' => '9876543210', 'card_last_digits' => '4242', 'bank_state' => 1]);
        app(BalanceLedger::class)->record($user, BalanceTransactionType::Topup, 25000, 25000, 'Пополнение банковской картой, счёт №'.$topup->id, $topup);

        $this->actingAs($administrator)
            ->get("/admin/balance-topups/{$topup->id}")
            ->assertOk()
            ->assertSee('Плательщик Пётр')
            ->assertSee('petr@example.test')
            ->assertSee('250,00 RUP')
            ->assertSee('9876543210')
            ->assertSee('•••• 4242')
            ->assertSee('Оплачен')
            ->assertSee('Пополнение банковской картой, счёт №'.$topup->id);
    }

    public function test_payment_page_of_unpaid_invoice_says_money_not_credited(): void
    {
        $administrator = $this->createAdministrator();
        $topup = $this->topup(User::factory()->create(), 'pending', 1000);

        $this->actingAs($administrator)
            ->get("/admin/balance-topups/{$topup->id}")
            ->assertOk()
            ->assertSee('Деньги ещё не зачислены')
            ->assertSee('Сверить с банком');
    }

    public function test_reconcile_credits_money_once_when_bank_confirms_payment(): void
    {
        $this->configureBank();
        $administrator = $this->createAdministrator();
        $user = User::factory()->create(['balance' => 10]);
        $topup = $this->topup($user, 'pending', 25000);

        $this->partialMock(WebPaymentClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getState')->andReturn(['state' => 1, 'sum' => 25000, 'currency' => '000', 'istest' => 0, 'rrn' => '42', 'lastdgt' => '1111']);
        });

        $this->actingAs($administrator);

        Livewire::test(ListBalanceTopups::class)
            ->callTableAction('reconcile', $topup)
            ->assertNotified('Платёж подтверждён банком');

        $this->assertSame('paid', $topup->fresh()->status);
        $this->assertSame('42', $topup->fresh()->rrn);
        $this->assertEquals('260.00', $user->fresh()->balance);
        $this->assertSame(1, BalanceTransaction::query()->where('user_id', $user->id)->count());

        // Повторная сверка уже закрытого счёта невозможна: действие скрыто, деньги не задвоятся.
        Livewire::test(ListBalanceTopups::class)->assertTableActionHidden('reconcile', $topup->fresh());
        $this->assertEquals('260.00', $user->fresh()->balance);
    }

    public function test_reconcile_without_configured_bank_explains_and_changes_nothing(): void
    {
        config(['services.webpayment.enabled' => false]);
        $administrator = $this->createAdministrator();
        $user = User::factory()->create(['balance' => 10]);
        $topup = $this->topup($user, 'pending', 25000);

        $this->actingAs($administrator);

        Livewire::test(ListBalanceTopups::class)
            ->callTableAction('reconcile', $topup)
            ->assertNotified('Банковский шлюз не настроен');

        $this->assertSame('pending', $topup->fresh()->status);
        $this->assertEquals('10.00', $user->fresh()->balance);
    }

    public function test_reconcile_from_payment_page_refreshes_status(): void
    {
        $this->configureBank();
        $administrator = $this->createAdministrator();
        $user = User::factory()->create(['balance' => 0]);
        $topup = $this->topup($user, 'pending', 1000);

        $this->partialMock(WebPaymentClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('getState')->andReturn(['state' => 3, 'sum' => 1000, 'currency' => '000', 'istest' => 0, 'rrn' => null, 'lastdgt' => null]);
        });

        $this->actingAs($administrator);

        Livewire::test(ViewBalanceTopup::class, ['record' => $topup->getKey()])
            ->callAction('reconcile')
            ->assertNotified();

        $this->assertSame('failed', $topup->fresh()->status);
        $this->assertEquals('0.00', $user->fresh()->balance);
    }

    public function test_overdue_payments_are_counted_in_navigation_badge(): void
    {
        $user = User::factory()->create();
        $this->assertNull(BalanceTopupResource::getNavigationBadge());

        $this->topup($user, 'pending', 100, ['expires_at' => now()->subMinutes(5)]);
        $this->topup($user, 'pending', 100);

        $this->assertSame('1', BalanceTopupResource::getNavigationBadge());
    }

    // ------------------------------------------------------------------ журнал

    public function test_journal_table_filters_by_type_direction_and_user(): void
    {
        $administrator = $this->createAdministrator();
        $anna = User::factory()->create();
        $boris = User::factory()->create();
        $ledger = app(BalanceLedger::class);
        $topup = $ledger->record($anna, BalanceTransactionType::Topup, 10000, 10000, 'Пополнение Анны');
        $vip = $ledger->record($anna, BalanceTransactionType::VipPurchase, -3000, 7000, 'VIP Анны');
        $other = $ledger->record($boris, BalanceTransactionType::Topup, 500, 500, 'Пополнение Бориса');

        $this->actingAs($administrator);

        Livewire::test(ListBalanceTransactions::class)
            ->assertCanSeeTableRecords([$topup, $vip, $other])
            ->filterTable('type', [BalanceTransactionType::VipPurchase->value])
            ->assertCanSeeTableRecords([$vip])
            ->assertCanNotSeeTableRecords([$topup, $other])
            ->resetTableFilters()
            ->filterTable('direction', 'credit')
            ->assertCanSeeTableRecords([$topup, $other])
            ->assertCanNotSeeTableRecords([$vip])
            ->resetTableFilters()
            ->filterTable('user_id', $boris->id)
            ->assertCanSeeTableRecords([$other])
            ->assertCanNotSeeTableRecords([$topup, $vip])
            ->resetTableFilters()
            ->searchTable('VIP Анны')
            ->assertCanSeeTableRecords([$vip])
            ->assertCanNotSeeTableRecords([$topup]);
    }

    public function test_journal_operation_page_links_to_its_source(): void
    {
        $administrator = $this->createAdministrator();
        $user = User::factory()->create();
        $topup = $this->topup($user, 'paid', 25000, ['rrn' => '31337']);
        $entry = app(BalanceLedger::class)->record(
            $user, BalanceTransactionType::Topup, 25000, 25000, 'Пополнение картой',
            $topup, ['rrn' => '31337', 'card_last_digits' => '4242', 'is_test' => true],
        );

        $this->actingAs($administrator)
            ->get("/admin/balance-transactions/{$entry->id}")
            ->assertOk()
            ->assertSee('Платёж картой, счёт № '.$topup->id)
            ->assertSee("/admin/balance-topups/{$topup->id}", false)
            ->assertSee('RRN банка')
            ->assertSee('31337')
            ->assertSee('Тестовый платёж');
    }

    // ------------------------------------------------------------------ VIP

    private function purchase(string $state = 'running'): VipPurchase
    {
        $product = Product::factory()->create();

        return VipPurchase::query()->create([
            'product_id' => $product->id,
            'user_id' => $product->seller->user_id,
            'days' => 7,
            'price_cents' => 7000,
            'starts_at' => $state === 'finished' ? now()->subDays(10) : now()->subDay(),
            'ends_at' => $state === 'finished' ? now()->subDays(3) : now()->addDays(6),
            'status' => $state === 'cancelled' ? 'cancelled' : 'active',
            'cancelled_at' => $state === 'cancelled' ? now() : null,
            'refunded_cents' => $state === 'cancelled' ? 5000 : 0,
        ]);
    }

    public function test_vip_purchases_table_filters_by_state(): void
    {
        $administrator = $this->createAdministrator();
        $running = $this->purchase('running');
        $finished = $this->purchase('finished');
        $cancelled = $this->purchase('cancelled');

        $this->actingAs($administrator);

        Livewire::test(ListVipPurchases::class)
            ->assertCanSeeTableRecords([$running, $finished, $cancelled])
            ->filterTable('state', 'running')
            ->assertCanSeeTableRecords([$running])
            ->assertCanNotSeeTableRecords([$finished, $cancelled])
            ->resetTableFilters()
            ->filterTable('state', 'cancelled')
            ->assertCanSeeTableRecords([$cancelled])
            ->assertCanNotSeeTableRecords([$running, $finished])
            ->resetTableFilters()
            ->filterTable('state', 'finished')
            ->assertCanSeeTableRecords([$finished])
            ->assertCanNotSeeTableRecords([$running, $cancelled]);
    }

    public function test_vip_purchase_page_shows_product_refund_and_journal(): void
    {
        $administrator = $this->createAdministrator();
        $purchase = $this->purchase('cancelled');
        app(BalanceLedger::class)->record($purchase->user, BalanceTransactionType::VipPurchase, -7000, 3000, 'VIP на 7 дн. для журнала', $purchase);
        app(BalanceLedger::class)->record($purchase->user, BalanceTransactionType::VipRefund, 5000, 8000, 'Возврат за дни VIP для журнала', $purchase);

        $this->actingAs($administrator)
            ->get("/admin/vip-purchases/{$purchase->id}")
            ->assertOk()
            ->assertSee($purchase->product->name)
            ->assertSee('Отключён досрочно')
            ->assertSee('50,00 RUP')
            ->assertSee('VIP на 7 дн. для журнала')
            ->assertSee('Возврат за дни VIP для журнала');
    }

    // ------------------------------------------------------------------ сводка и карточка пользователя

    public function test_overview_widget_counts_real_payments_only(): void
    {
        $administrator = $this->createAdministrator();
        $user = User::factory()->create(['balance' => 100]);
        $this->topup($user, 'paid', 150000);
        $this->topup($user, 'paid', 999900, ['is_test' => true]);
        $this->topup($user, 'pending', 500, ['expires_at' => now()->subHour()]);
        $this->topup($user, 'failed', 700);

        $this->actingAs($administrator);

        Livewire::test(FinanceOverviewWidget::class)
            ->assertSee('Пополнено сегодня')
            ->assertSee('1 500,00 RUP')
            ->assertDontSee('9 999,00 RUP')
            ->assertSee('Зависших: 1')
            ->assertSee('Выручка с VIP');
    }

    public function test_overview_widget_is_shown_above_payments_and_on_dashboard(): void
    {
        $administrator = $this->createAdministrator();

        $this->actingAs($administrator)->get('/admin/balance-topups')->assertOk()->assertSee('Пополнено сегодня');
        $this->actingAs($administrator)->get('/admin')->assertOk()->assertSee('Пополнено сегодня');
    }

    public function test_user_card_shows_balance_journal_payments_and_vip_tabs(): void
    {
        $administrator = $this->createAdministrator();
        $user = User::factory()->create();
        $topup = $this->topup($user, 'paid', 25000);
        $entry = app(BalanceLedger::class)->record($user, BalanceTransactionType::Topup, 25000, 25000, 'Пополнение для карточки', $topup);
        $purchase = $this->purchase();
        $purchase->forceFill(['user_id' => $user->id])->save();
        $stranger = $this->topup(User::factory()->create(), 'paid', 777);

        $this->actingAs($administrator);

        Livewire::test(BalanceTransactionsRelationManager::class, ['ownerRecord' => $user, 'pageClass' => EditUser::class])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$entry]);

        Livewire::test(BalanceTopupsRelationManager::class, ['ownerRecord' => $user, 'pageClass' => EditUser::class])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$topup])
            ->assertCanNotSeeTableRecords([$stranger]);

        Livewire::test(VipPurchasesRelationManager::class, ['ownerRecord' => $user, 'pageClass' => EditUser::class])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$purchase]);

        $this->get("/admin/users/{$user->id}/edit")->assertOk();
    }
}
