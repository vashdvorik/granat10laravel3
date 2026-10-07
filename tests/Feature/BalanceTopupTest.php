<?php

namespace Tests\Feature;

use App\Models\BalanceTopup;
use App\Models\User;
use App\Services\WebPayment\WebPaymentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class BalanceTopupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        config([
            'services.webpayment.enabled' => true,
            'services.webpayment.merchant_login' => '000123',
            'services.webpayment.merchant_pass' => 'secret',
            'services.webpayment.currency' => '000',
            'services.webpayment.is_test' => false,
            'services.webpayment.min_amount' => 10,
            'services.webpayment.max_amount' => 50000,
        ]);
    }

    private function topup(User $user, int $cents = 150000, bool $test = false): BalanceTopup
    {
        return BalanceTopup::query()->create([
            'user_id' => $user->id,
            'amount_cents' => $cents,
            'currency' => '000',
            'status' => BalanceTopup::STATUS_PENDING,
            'is_test' => $test,
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    private function bankState(int $state, int $sum, int $istest = 0, string $currency = '000'): array
    {
        return ['state' => $state, 'sum' => $sum, 'currency' => $currency, 'istest' => $istest, 'rrn' => '0007458712', 'lastdgt' => '0123'];
    }

    private function mockState(array|RuntimeException $result, int $times = 1): void
    {
        $this->partialMock(WebPaymentClient::class, function (MockInterface $mock) use ($result, $times) {
            $expectation = $mock->shouldReceive('getState')->times($times);
            $result instanceof RuntimeException ? $expectation->andThrow($result) : $expectation->andReturn($result);
        });
    }

    private function resultParams(BalanceTopup $topup, string $date = '01012025'): array
    {
        $client = new WebPaymentClient;

        return [
            'status' => 'paid',
            'paymentsum' => (string) $topup->amount_cents,
            'paymentcurrency' => '000',
            'invoiceid' => $topup->invoiceId(),
            'date' => $date,
            'istest' => '0',
            'signature' => $client->paidSignature($topup->invoiceId(), (string) $topup->amount_cents, '000', $date),
        ];
    }

    public function test_payment_signature_matches_documentation_algorithm(): void
    {
        $fields = (new WebPaymentClient)->paymentFields('123456', 1524, 'Счет №123456', 30, false);

        $this->assertSame(md5('000123:123456:0:1524:000:Счет №123456:secret'), $fields['SignatureValue']);
        $this->assertSame(1524, $fields['RequestSum']);
    }

    public function test_guest_cannot_open_topup_page(): void
    {
        $this->get(route('account.user.balance.topup'))->assertRedirect(route('login'));
    }

    public function test_topup_page_renders_for_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('account.user.balance.topup'))
            ->assertOk()
            ->assertSee('Пополнение баланса');
    }

    public function test_store_creates_pending_topup_and_posts_signed_form_to_bank(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('account.user.balance.topup.store'), ['amount' => '500,50']);

        $topup = BalanceTopup::query()->firstOrFail();
        $this->assertSame(50050, $topup->amount_cents);
        $this->assertSame('pending', $topup->status);
        $response->assertOk()
            ->assertSee('https://epay.apb.online/PaymentStart', false)
            ->assertSee(md5('000123:'.$topup->id.':0:50050:000:Пополнение баланса, счёт №'.$topup->id.':secret'), false);
    }

    public function test_store_rejects_amount_outside_limits(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.user.balance.topup.store'), ['amount' => '5'])
            ->assertSessionHasErrors('amount');
        $this->actingAs($user)->post(route('account.user.balance.topup.store'), ['amount' => 'abc'])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, BalanceTopup::query()->count());
    }

    public function test_result_with_valid_signature_credits_balance_once(): void
    {
        $user = User::factory()->create();
        $topup = $this->topup($user);
        $this->mockState($this->bankState(1, 150000));

        $params = $this->resultParams($topup);
        $this->post(route('payments.webpayment.result'), $params)->assertOk()->assertSee('OK');
        $this->post(route('payments.webpayment.result'), $params)->assertOk();

        $this->assertEquals(1500, $user->fresh()->getRawOriginal('balance'));
        $this->assertSame('paid', $topup->fresh()->status);
        $this->assertSame('0007458712', $topup->fresh()->rrn);
    }

    public function test_result_with_wrong_signature_is_rejected_without_calling_bank(): void
    {
        $user = User::factory()->create();
        $topup = $this->topup($user);
        $this->mockState($this->bankState(1, 150000), 0);

        $params = $this->resultParams($topup);
        $params['signature'] = str_repeat('a', 32);

        $this->post(route('payments.webpayment.result'), $params)->assertStatus(400);

        $this->assertSame('pending', $topup->fresh()->status);
        $this->assertEquals(0, $user->fresh()->getRawOriginal('balance'));
    }

    public function test_balance_is_not_credited_when_bank_says_not_paid(): void
    {
        $user = User::factory()->create();
        $topup = $this->topup($user);
        $this->mockState($this->bankState(0, 150000));

        $this->post(route('payments.webpayment.result'), $this->resultParams($topup))->assertOk();

        $this->assertSame('pending', $topup->fresh()->status);
        $this->assertEquals(0, $user->fresh()->getRawOriginal('balance'));
    }

    public function test_balance_is_not_credited_when_bank_sum_differs(): void
    {
        $user = User::factory()->create();
        $topup = $this->topup($user);
        $this->mockState($this->bankState(1, 100));

        $this->post(route('payments.webpayment.result'), $this->resultParams($topup))->assertOk();

        $this->assertSame('failed', $topup->fresh()->status);
        $this->assertEquals(0, $user->fresh()->getRawOriginal('balance'));
    }

    public function test_test_payment_is_not_credited_in_production_mode(): void
    {
        $user = User::factory()->create();
        $topup = $this->topup($user);
        $this->mockState($this->bankState(1, 150000, 1));

        $this->post(route('payments.webpayment.result'), $this->resultParams($topup))->assertOk();

        $this->assertSame('failed', $topup->fresh()->status);
        $this->assertEquals(0, $user->fresh()->getRawOriginal('balance'));
    }

    public function test_success_url_settles_via_get_state_and_redirects_to_cabinet(): void
    {
        $user = User::factory()->create();
        $topup = $this->topup($user, 25000);
        $this->mockState($this->bankState(1, 25000));

        $this->post(route('payments.webpayment.success'), ['invoiceid' => $topup->invoiceId()])
            ->assertRedirect(route('account.user.balance.topup'));

        $this->assertEquals(250, $user->fresh()->getRawOriginal('balance'));
    }

    public function test_fail_url_marks_failed_payment(): void
    {
        $user = User::factory()->create();
        $topup = $this->topup($user);
        $this->mockState($this->bankState(3, 150000));

        $this->get(route('payments.webpayment.fail', ['invoiceid' => $topup->invoiceId()]))
            ->assertRedirect(route('account.user.balance.topup'));

        $this->assertSame('failed', $topup->fresh()->status);
    }

    public function test_reconcile_command_credits_pending_topups(): void
    {
        $user = User::factory()->create();
        $topup = $this->topup($user);
        $topup->forceFill(['created_at' => now()->subMinutes(5)])->save();
        $this->mockState($this->bankState(1, 150000));

        $this->artisan('balance:reconcile-topups')->assertSuccessful();

        $this->assertSame('paid', $topup->fresh()->status);
        $this->assertEquals(1500, $user->fresh()->getRawOriginal('balance'));
    }

    public function test_pending_topup_expires_after_deadline_when_bank_is_unreachable(): void
    {
        $user = User::factory()->create();
        $topup = $this->topup($user);
        $topup->forceFill(['expires_at' => now()->subHour()])->save();
        $this->mockState(new RuntimeException('down'));

        $this->post(route('payments.webpayment.success'), ['invoiceid' => $topup->invoiceId()]);

        $this->assertSame('expired', $topup->fresh()->status);
    }
}
