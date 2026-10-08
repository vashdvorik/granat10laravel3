<?php

namespace Tests\Feature;

use App\Models\BalanceTopup;
use App\Models\User;
use App\Services\WebPayment\BalanceTopupService;
use App\Services\WebPayment\WebPaymentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Устойчивость пополнения баланса к особенностям сервера (нет внешнего ключа, сбой миграции). */
class BalanceTopupResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_topups_migration_can_be_rerun_after_partial_failure(): void
    {
        // Имитация сервера: таблица осталась после сбоя на внешнем ключе
        $this->assertTrue(Schema::hasTable('balance_topups'));

        $migration = require database_path('migrations/2026_10_07_000001_create_balance_topups_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('balance_topups'));
        $this->assertTrue(Schema::hasColumns('balance_topups', ['user_id', 'amount_cents', 'status']));
    }

    public function test_paid_topup_of_deleted_user_does_not_crash_settlement(): void
    {
        config([
            'services.webpayment.enabled' => true,
            'services.webpayment.merchant_login' => 'x',
            'services.webpayment.merchant_pass' => 'y',
            'services.webpayment.currency' => '000',
            'services.webpayment.is_test' => false,
        ]);
        $user = User::factory()->create();
        $topup = BalanceTopup::query()->create([
            'user_id' => $user->id, 'amount_cents' => 1000, 'currency' => '000', 'status' => 'pending',
            'is_test' => false, 'expires_at' => now()->addMinutes(30),
        ]);
        // как на сервере без внешнего ключа: пользователь удалён, счёт остался
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::table('users')->where('id', $user->id)->delete();

        $this->partialMock(WebPaymentClient::class, function ($mock) {
            $mock->shouldReceive('getState')->andReturn(['state' => 1, 'sum' => 1000, 'currency' => '000', 'istest' => 0, 'rrn' => '1', 'lastdgt' => '0123']);
        });

        $settled = app(BalanceTopupService::class)->settle($topup->fresh());

        $this->assertSame('failed', $settled->status);
    }
}
