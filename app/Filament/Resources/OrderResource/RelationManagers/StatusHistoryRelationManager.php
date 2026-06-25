<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\OrderStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StatusHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistory';

    protected static ?string $title = 'История статусов';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('from_status')
                    ->label('Предыдущий статус')
                    ->formatStateUsing(fn (?OrderStatus $state): string => $state?->label() ?? 'Создание'),
                Tables\Columns\TextColumn::make('to_status')
                    ->label('Новый статус')
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->badge(),
                Tables\Columns\TextColumn::make('changedBy.email')
                    ->label('Кто изменил')
                    ->placeholder('Покупатель / система'),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Комментарий')
                    ->wrap()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
