<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Enums\ProductModerationStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ModerationActionsRelationManager extends RelationManager
{
    protected static string $relationship = 'moderationActions';

    protected static ?string $title = 'История модерации';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('actor.name')
                    ->label('Кто выполнил')
                    ->placeholder('Система'),
                Tables\Columns\TextColumn::make('action')
                    ->label('Действие')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'submitted' => 'Отправлен на проверку',
                        'approved' => 'Одобрен',
                        'changes_requested' => 'На доработку',
                        'rejected' => 'Отклонён',
                        default => $state,
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('new_status')
                    ->label('Новый статус')
                    ->formatStateUsing(fn (string $state): string => ProductModerationStatus::from($state)->label())
                    ->color(fn (string $state): string => ProductModerationStatus::from($state)->color())
                    ->badge(),
                Tables\Columns\TextColumn::make('reason_code')
                    ->label('Код причины')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Комментарий')
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
