<?php

namespace App\Filament\Resources;

use App\Enums\BalanceTransactionType;
use App\Filament\Resources\BalanceTransactionResource\Pages;
use App\Filament\Resources\UserResource;
use App\Filament\Support\FinanceTable;
use App\Models\BalanceTopup;
use App\Models\BalanceTransaction;
use App\Models\VipPurchase;
use App\Support\Money;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

/** Единый журнал баланса всех пользователей: каждая операция, которая меняла деньги на балансе. */
class BalanceTransactionResource extends Resource
{
    protected static ?string $model = BalanceTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Финансы';

    protected static ?string $navigationLabel = 'Журнал баланса';

    protected static ?string $modelLabel = 'операция';

    protected static ?string $pluralModelLabel = 'операции';

    protected static ?int $navigationSort = 41;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return static::configureTable($table, withUser: true);
    }

    /**
     * Общая таблица для раздела и для вкладки «Журнал баланса» в карточке пользователя
     * (там колонка пользователя не нужна).
     */
    public static function configureTable(Table $table, bool $withUser): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns(array_values(array_filter([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Когда')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                $withUser ? FinanceTable::userColumn() : null,
                Tables\Columns\TextColumn::make('type')
                    ->label('Операция')
                    ->badge()
                    ->formatStateUsing(fn (BalanceTransactionType $state): string => $state->label())
                    ->color(fn (BalanceTransactionType $state): string => $state->color()),
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(70)
                    ->tooltip(fn (BalanceTransaction $record): string => $record->description)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Сумма')
                    ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state, true))
                    ->color(fn (mixed $state): string => (int) $state >= 0 ? 'success' : 'danger')
                    ->weight(FontWeight::SemiBold)
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->label('Поступило')
                            ->query(fn (QueryBuilder $query): QueryBuilder => $query->where('amount_cents', '>', 0))
                            ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state)),
                        Sum::make()
                            ->label('Списано')
                            ->query(fn (QueryBuilder $query): QueryBuilder => $query->where('amount_cents', '<', 0))
                            ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state)),
                    ]),
                Tables\Columns\TextColumn::make('balance_after_cents')
                    ->label('Баланс после')
                    ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state))
                    ->alignEnd()
                    ->toggleable(),
            ])))
            ->filters(array_values(array_filter([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип операции')
                    ->multiple()
                    ->options(BalanceTransactionType::options()),
                Tables\Filters\SelectFilter::make('direction')
                    ->label('Направление')
                    ->options(['credit' => 'Поступления', 'debit' => 'Списания'])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'credit' => $query->where('amount_cents', '>', 0),
                        'debit' => $query->where('amount_cents', '<', 0),
                        default => $query,
                    }),
                $withUser ? FinanceTable::userFilter() : null,
                FinanceTable::periodFilter('created_at', 'Период'),
            ])))
            ->actions([
                Tables\Actions\ViewAction::make()->label('Подробнее'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Операция')
                ->icon('heroicon-o-queue-list')
                ->schema([
                    Infolists\Components\TextEntry::make('type')
                        ->label('Тип')
                        ->badge()
                        ->formatStateUsing(fn (BalanceTransactionType $state): string => $state->label())
                        ->color(fn (BalanceTransactionType $state): string => $state->color()),
                    Infolists\Components\TextEntry::make('amount_cents')
                        ->label('Сумма')
                        ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state, true))
                        ->color(fn (mixed $state): string => (int) $state >= 0 ? 'success' : 'danger')
                        ->size(TextEntrySize::Large)
                        ->weight(FontWeight::Bold),
                    Infolists\Components\TextEntry::make('balance_after_cents')
                        ->label('Баланс после операции')
                        ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state)),
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Когда')
                        ->dateTime('d.m.Y H:i:s'),
                    Infolists\Components\TextEntry::make('description')
                        ->label('Описание')
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 4]),

            Infolists\Components\Section::make('Пользователь')
                ->icon('heroicon-o-user')
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')
                        ->label('Имя')
                        ->placeholder('Пользователь удалён')
                        ->url(fn (BalanceTransaction $record): ?string => $record->user
                            ? UserResource::getUrl('edit', ['record' => $record->user])
                            : null)
                        ->color('primary'),
                    Infolists\Components\TextEntry::make('user.email')
                        ->label('Email')
                        ->copyable()
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('user.balance')
                        ->label('Баланс сейчас')
                        ->formatStateUsing(fn (mixed $state): string => Money::rup(Money::fromDecimal($state)))
                        ->placeholder('—'),
                ])
                ->columns(['default' => 1, 'md' => 3]),

            Infolists\Components\Section::make('Источник операции')
                ->icon('heroicon-o-link')
                ->schema([
                    Infolists\Components\TextEntry::make('reference')
                        ->label('Связанная запись')
                        ->state(fn (BalanceTransaction $record): string => static::referenceLabel($record))
                        ->url(fn (BalanceTransaction $record): ?string => static::referenceUrl($record))
                        ->color(fn (BalanceTransaction $record): string => static::referenceUrl($record) ? 'primary' : 'gray'),
                    Infolists\Components\KeyValueEntry::make('meta')
                        ->label('Технические данные')
                        ->keyLabel('Параметр')
                        ->valueLabel('Значение')
                        ->placeholder('Нет')
                        ->state(fn (BalanceTransaction $record): array => collect($record->meta ?? [])
                            ->mapWithKeys(fn (mixed $value, string $key): array => [
                                static::metaLabel($key) => match (true) {
                                    is_bool($value) => $value ? 'да' : 'нет',
                                    is_scalar($value) => (string) $value,
                                    default => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
                                },
                            ])
                            ->all()),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function referenceLabel(BalanceTransaction $record): string
    {
        return match ($record->reference_type) {
            (new BalanceTopup)->getMorphClass() => 'Платёж картой, счёт № '.$record->reference_id,
            (new VipPurchase)->getMorphClass() => 'Покупка VIP № '.$record->reference_id,
            default => 'Нет (ручная операция или внутренний перевод)',
        };
    }

    public static function referenceUrl(BalanceTransaction $record): ?string
    {
        return match ($record->reference_type) {
            (new BalanceTopup)->getMorphClass() => BalanceTopupResource::getUrl('view', ['record' => $record->reference_id]),
            (new VipPurchase)->getMorphClass() => VipPurchaseResource::getUrl('view', ['record' => $record->reference_id]),
            default => null,
        };
    }

    private static function metaLabel(string $key): string
    {
        return match ($key) {
            'rrn' => 'RRN банка',
            'card_last_digits' => 'Последние цифры карты',
            'is_test' => 'Тестовый платёж',
            'product_id' => 'ID товара',
            'days' => 'Дней VIP',
            default => $key,
        };
    }

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record === null ? null : 'Операция № '.$record->getKey();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBalanceTransactions::route('/'),
            'view' => Pages\ViewBalanceTransaction::route('/{record}'),
        ];
    }
}
