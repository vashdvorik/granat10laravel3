<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\BalanceTopupResource;
use App\Models\BalanceTopup;
use App\Support\Money;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Платежи картой, которые пользователь создавал. */
class BalanceTopupsRelationManager extends RelationManager
{
    protected static string $relationship = 'balanceTopups';

    protected static ?string $title = 'Платежи картой';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Счёт')
                    ->formatStateUsing(fn (mixed $state): string => '№ '.$state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Сумма')
                    ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (BalanceTopup $record): string => $record->isOverdue()
                        ? 'Завис: банк не ответил'
                        : $record->statusLabel())
                    ->color(fn (BalanceTopup $record): string => $record->isOverdue() ? 'danger' : $record->statusColor()),
                Tables\Columns\TextColumn::make('is_test')
                    ->label('Режим')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Тест' : 'Боевой')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('rrn')
                    ->label('RRN')
                    ->copyable()
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        BalanceTopup::STATUS_PAID => 'Оплачен',
                        BalanceTopup::STATUS_PENDING => 'Ожидает оплаты',
                        BalanceTopup::STATUS_FAILED => 'Ошибка оплаты',
                        BalanceTopup::STATUS_CANCELLED => 'Отменён',
                        BalanceTopup::STATUS_EXPIRED => 'Просрочен',
                    ]),
            ])
            ->recordUrl(fn (BalanceTopup $record): string => BalanceTopupResource::getUrl('view', ['record' => $record]))
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
