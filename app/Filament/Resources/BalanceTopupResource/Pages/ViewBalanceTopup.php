<?php

namespace App\Filament\Resources\BalanceTopupResource\Pages;

use App\Filament\Resources\BalanceTopupResource;
use App\Models\BalanceTopup;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBalanceTopup extends ViewRecord
{
    protected static string $resource = BalanceTopupResource::class;

    public function getTitle(): string
    {
        return 'Платёж № '.$this->getRecord()->getKey();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reconcile')
                ->label('Сверить с банком')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => $this->getRecord()->isPending())
                ->requiresConfirmation()
                ->modalHeading('Сверить платёж с банком')
                ->modalDescription('Запросим у банка состояние счёта. Если оплата прошла, деньги будут зачислены на баланс один раз, повторная сверка ничего не задвоит.')
                ->action(function (): void {
                    /** @var BalanceTopup $record */
                    $record = $this->getRecord();

                    BalanceTopupResource::reconcile($record);

                    $this->record = $record->fresh(['user', 'ledgerEntry']);
                }),
        ];
    }
}
