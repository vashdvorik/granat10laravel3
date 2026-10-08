<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\BalanceTransactionResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Все операции по балансу пользователя в его карточке. */
class BalanceTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'balanceTransactions';

    protected static ?string $title = 'Журнал баланса';

    public function table(Table $table): Table
    {
        return BalanceTransactionResource::configureTable($table, withUser: false)
            ->recordUrl(fn ($record): string => BalanceTransactionResource::getUrl('view', ['record' => $record]))
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Подробнее')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record): string => BalanceTransactionResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
