<?php

namespace App\Filament\Resources\BalanceTopupResource\Pages;

use App\Filament\Resources\BalanceTopupResource;
use App\Filament\Widgets\FinanceOverviewWidget;
use App\Services\WebPayment\BalanceTopupService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListBalanceTopups extends ListRecords
{
    protected static string $resource = BalanceTopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reconcileAll')
                ->label('Сверить все ожидающие')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Сверить ожидающие платежи с банком')
                ->modalDescription('Запросим у банка состояние всех счетов, которые ждут оплаты (до 200 за раз). То же самое планировщик делает каждые 5 минут.')
                ->action(function (BalanceTopupService $service): void {
                    if (! $service->isAvailable()) {
                        Notification::make()
                            ->title('Банковский шлюз не настроен')
                            ->body('Проверьте настройки WEBPAYMENT_* в .env.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $closed = $service->reconcilePending();

                    Notification::make()
                        ->title($closed > 0 ? "Закрыто платежей: {$closed}" : 'Новых результатов нет')
                        ->body($closed > 0 ? 'Статусы обновлены, деньги по оплаченным счетам зачислены.' : 'Банк пока не сообщил итог по ожидающим счетам.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [FinanceOverviewWidget::class];
    }
}
