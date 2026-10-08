<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\VipPurchaseResource\Pages;
use App\Filament\Support\FinanceTable;
use App\Models\VipPurchase;
use App\Support\Money;
use App\Support\Plural;
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

/** Покупки VIP-размещения: кто, за какой товар, на какой срок и сколько заплатил. Только просмотр. */
class VipPurchaseResource extends Resource
{
    protected static ?string $model = VipPurchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Финансы';

    protected static ?string $navigationLabel = 'Покупки VIP';

    protected static ?string $modelLabel = 'покупка VIP';

    protected static ?string $pluralModelLabel = 'покупки VIP';

    protected static ?int $navigationSort = 42;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return static::configureTable($table, withUser: true);
    }

    /** Общая таблица для раздела и для вкладки в карточке пользователя. */
    public static function configureTable(Table $table, bool $withUser): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns(array_values(array_filter([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Куплено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Товар')
                    ->placeholder('Товар удалён')
                    ->limit(36)
                    ->tooltip(fn (VipPurchase $record): ?string => $record->product?->name)
                    ->url(fn (VipPurchase $record): ?string => $record->product
                        ? ProductResource::getUrl('edit', ['record' => $record->product])
                        : null)
                    ->searchable(),
                $withUser ? FinanceTable::userColumn('Продавец') : null,
                Tables\Columns\TextColumn::make('days')
                    ->label('Срок')
                    ->formatStateUsing(fn (mixed $state): string => Plural::days((int) $state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Действует до')
                    ->dateTime('d.m.Y H:i')
                    ->description(fn (VipPurchase $record): string => 'с '.$record->starts_at->format('d.m.Y H:i'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_cents')
                    ->label('Стоимость')
                    ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state))
                    ->weight(FontWeight::SemiBold)
                    ->alignEnd()
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Итого')
                            ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state)),
                    ),
                Tables\Columns\TextColumn::make('refunded_cents')
                    ->label('Возврат')
                    ->formatStateUsing(fn (mixed $state): string => (int) $state > 0 ? Money::rup((int) $state) : '—')
                    ->color(fn (mixed $state): string => (int) $state > 0 ? 'success' : 'gray')
                    ->alignEnd()
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Возвращено')
                            ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state)),
                    ),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (VipPurchase $record): string => $record->statusLabel())
                    ->color(fn (VipPurchase $record): string => $record->statusColor()),
            ])))
            ->filters(array_values(array_filter([
                Tables\Filters\SelectFilter::make('state')
                    ->label('Статус')
                    ->options([
                        'running' => 'Действует сейчас',
                        'scheduled' => 'Ожидает начала',
                        'finished' => 'Завершён',
                        'cancelled' => 'Отключён досрочно',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'cancelled' => $query->where('status', VipPurchase::STATUS_CANCELLED),
                        'running' => $query->where('status', VipPurchase::STATUS_ACTIVE)
                            ->where('starts_at', '<=', now())->where('ends_at', '>', now()),
                        'scheduled' => $query->where('status', VipPurchase::STATUS_ACTIVE)->where('starts_at', '>', now()),
                        'finished' => $query->where('status', VipPurchase::STATUS_ACTIVE)->where('ends_at', '<=', now()),
                        default => $query,
                    }),
                $withUser ? FinanceTable::userFilter() : null,
                FinanceTable::periodFilter('created_at', 'Куплено'),
            ])))
            ->actions([
                Tables\Actions\ViewAction::make()->label('Подробнее'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Покупка')
                ->icon('heroicon-o-star')
                ->schema([
                    Infolists\Components\TextEntry::make('status')
                        ->label('Статус')
                        ->badge()
                        ->formatStateUsing(fn (VipPurchase $record): string => $record->statusLabel())
                        ->color(fn (VipPurchase $record): string => $record->statusColor()),
                    Infolists\Components\TextEntry::make('price_cents')
                        ->label('Стоимость')
                        ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state))
                        ->size(TextEntrySize::Large)
                        ->weight(FontWeight::Bold),
                    Infolists\Components\TextEntry::make('days')
                        ->label('Срок')
                        ->formatStateUsing(fn (mixed $state): string => Plural::days((int) $state)),
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Куплено')
                        ->dateTime('d.m.Y H:i:s'),
                    Infolists\Components\TextEntry::make('starts_at')
                        ->label('VIP с')
                        ->dateTime('d.m.Y H:i'),
                    Infolists\Components\TextEntry::make('ends_at')
                        ->label('VIP до')
                        ->dateTime('d.m.Y H:i'),
                ])
                ->columns(['default' => 1, 'md' => 3]),

            Infolists\Components\Section::make('Досрочное отключение')
                ->icon('heroicon-o-arrow-uturn-left')
                ->visible(fn (VipPurchase $record): bool => $record->isCancelled())
                ->schema([
                    Infolists\Components\TextEntry::make('cancelled_at')
                        ->label('Отключён')
                        ->dateTime('d.m.Y H:i:s'),
                    Infolists\Components\TextEntry::make('refunded_cents')
                        ->label('Возвращено на баланс')
                        ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state))
                        ->color('success'),
                ])
                ->columns(['default' => 1, 'md' => 2]),

            Infolists\Components\Section::make('Товар и продавец')
                ->icon('heroicon-o-shopping-bag')
                ->schema([
                    Infolists\Components\TextEntry::make('product.name')
                        ->label('Товар')
                        ->placeholder('Товар удалён')
                        ->url(fn (VipPurchase $record): ?string => $record->product
                            ? ProductResource::getUrl('edit', ['record' => $record->product])
                            : null)
                        ->color('primary'),
                    Infolists\Components\TextEntry::make('user.name')
                        ->label('Продавец')
                        ->placeholder('Пользователь удалён')
                        ->url(fn (VipPurchase $record): ?string => $record->user
                            ? UserResource::getUrl('edit', ['record' => $record->user])
                            : null)
                        ->color('primary'),
                    Infolists\Components\TextEntry::make('user.email')
                        ->label('Email')
                        ->copyable()
                        ->placeholder('—'),
                ])
                ->columns(['default' => 1, 'md' => 3]),

            Infolists\Components\Section::make('Операции в журнале баланса')
                ->icon('heroicon-o-queue-list')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('ledgerEntries')
                        ->hiddenLabel()
                        ->placeholder('Операций нет')
                        ->schema([
                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Когда')
                                ->dateTime('d.m.Y H:i'),
                            Infolists\Components\TextEntry::make('description')
                                ->label('Описание'),
                            Infolists\Components\TextEntry::make('amount_cents')
                                ->label('Сумма')
                                ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state, true))
                                ->color(fn (mixed $state): string => (int) $state >= 0 ? 'success' : 'danger'),
                        ])
                        ->columns(['default' => 1, 'md' => 3]),
                ]),
        ]);
    }

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record === null ? null : 'Покупка VIP № '.$record->getKey();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['product', 'user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVipPurchases::route('/'),
            'view' => Pages\ViewVipPurchase::route('/{record}'),
        ];
    }
}
