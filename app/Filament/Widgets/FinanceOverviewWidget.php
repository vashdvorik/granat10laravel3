<?php

namespace App\Filament\Widgets;

use App\Models\BalanceTopup;
use App\Models\User;
use App\Models\VipPurchase;
use App\Support\Money;
use App\Support\Plural;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

/** Сводка по деньгам: пополнения, зависшие платежи, обязательства перед пользователями, выручка с VIP. */
class FinanceOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    // Запросы лёгкие: показываем сводку сразу, без «серой заглушки» на время подгрузки.
    protected static bool $isLazy = false;

    protected static ?string $pollingInterval = '60s';

    protected ?string $heading = 'Финансы';

    protected ?string $description = 'Тестовые платежи в суммах не учитываются.';

    public static function canView(): bool
    {
        return Gate::allows('access-admin');
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $paidReal = fn () => BalanceTopup::query()
            ->where('status', BalanceTopup::STATUS_PAID)
            ->where('is_test', false);

        $todayQuery = $paidReal()->where('paid_at', '>=', now()->startOfDay());
        $todayCents = (int) (clone $todayQuery)->sum('amount_cents');
        $todayCount = (clone $todayQuery)->count();

        $monthCents = (int) $paidReal()->where('paid_at', '>=', now()->subDays(30))->sum('amount_cents');

        $pending = BalanceTopup::query()->where('status', BalanceTopup::STATUS_PENDING);
        $pendingCount = (clone $pending)->count();
        $overdueCount = (clone $pending)->where('expires_at', '<', now())->count();

        $failedCount = BalanceTopup::query()
            ->where('status', BalanceTopup::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $balancesCents = Money::fromDecimal(User::query()->sum('balance'));

        $vipQuery = VipPurchase::query()->where('created_at', '>=', now()->subDays(30));
        $vipCount = (clone $vipQuery)->count();
        $vipNetCents = (int) (clone $vipQuery)->sum('price_cents') - (int) (clone $vipQuery)->sum('refunded_cents');

        return [
            Stat::make('Пополнено сегодня', Money::rup($todayCents))
                ->description($todayCount.' '.Plural::ru($todayCount, 'платёж', 'платежа', 'платежей'))
                ->descriptionIcon('heroicon-m-credit-card')
                ->color($todayCents > 0 ? 'success' : 'gray'),

            Stat::make('Пополнено за 30 дней', Money::rup($monthCents))
                ->description('График: последние 14 дней')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->chart($this->dailyPaidChart(14))
                ->color('success'),

            Stat::make('Ждут оплаты', (string) $pendingCount)
                ->description($overdueCount > 0
                    ? 'Зависших: '.$overdueCount.'. Нужна сверка с банком'
                    : 'Зависших нет')
                ->descriptionIcon($overdueCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($overdueCount > 0 ? 'danger' : 'gray'),

            Stat::make('Не прошли за 7 дней', (string) $failedCount)
                ->description('Отказ банка или несовпадение суммы')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($failedCount > 0 ? 'warning' : 'gray'),

            Stat::make('На балансах пользователей', Money::rup($balancesCents))
                ->description('Сумма всех балансов — обязательства перед пользователями')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Выручка с VIP за 30 дней', Money::rup($vipNetCents))
                ->description($vipCount.' '.Plural::ru($vipCount, 'покупка', 'покупки', 'покупок').', с учётом возвратов')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }

    /** Сумма реальных оплат по дням, от старых к новым: для мини-графика. */
    private function dailyPaidChart(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $byDay = BalanceTopup::query()
            ->where('status', BalanceTopup::STATUS_PAID)
            ->where('is_test', false)
            ->where('paid_at', '>=', $from)
            ->get(['paid_at', 'amount_cents'])
            ->groupBy(fn (BalanceTopup $topup): string => $topup->paid_at->format('Y-m-d'))
            ->map(fn ($group): int => (int) $group->sum('amount_cents'));

        return collect(range(0, $days - 1))
            ->map(fn (int $offset): float => ($byDay[$from->copy()->addDays($offset)->format('Y-m-d')] ?? 0) / 100)
            ->all();
    }
}
