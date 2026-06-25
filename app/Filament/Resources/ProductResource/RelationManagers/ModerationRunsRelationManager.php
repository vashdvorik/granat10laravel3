<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ModerationRunsRelationManager extends RelationManager
{
    protected static string $relationship = 'moderationRuns';

    protected static ?string $title = 'Автоматические проверки';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Провайдер')
                    ->badge(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Модель')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('prompt_version')
                    ->label('Версия prompt')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('text_result.policy_version')
                    ->label('Версия правил')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        'processing', 'retrying' => 'warning',
                        'stale' => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('decision')
                    ->label('Решение ИИ')
                    ->badge()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('manual_decision')
                    ->label('Решение модератора')
                    ->badge()
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('agrees_with_manual')
                    ->label('Совпало')
                    ->boolean()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('risk_score')
                    ->label('Риск')
                    ->numeric(2)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('latency_ms')
                    ->label('Время, мс')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Ошибка')
                    ->limit(100)
                    ->tooltip(fn ($record): ?string => $record->error_message)
                    ->placeholder('—'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('details')
                    ->label('Подробнее')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Детали автоматической проверки')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть')
                    ->modalContent(fn ($record) => view(
                        'filament.resources.product-resource.moderation-run-details',
                        ['run' => $record]
                    )),
            ])
            ->bulkActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
