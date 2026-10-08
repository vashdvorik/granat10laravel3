<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BalanceTopupResource\Pages;
use App\Filament\Resources\UserResource;
use App\Filament\Support\FinanceTable;
use App\Models\BalanceTopup;
use App\Services\WebPayment\BalanceTopupService;
use App\Support\Money;
use Filament\Infolists;
use Filament\Infolists\Components\TextEntry\TextEntrySize;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/** Платежи картой через банк: от создания счёта до зачисления денег. Только просмотр и сверка с банком. */
class BalanceTopupResource extends Resource
{
    protected static ?string $model = BalanceTopup::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Финансы';

    protected static ?string $navigationLabel = 'Платежи';

    protected static ?string $modelLabel = 'платёж';

    protected static ?string $pluralModelLabel = 'платежи';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'id';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $overdue = BalanceTopup::query()
            ->where('status', BalanceTopup::STATUS_PENDING)
            ->where('expires_at', '<', now())
            ->count();

        return $overdue > 0 ? (string) $overdue : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Зависшие платежи: срок счёта вышел, а банк не подтвердил оплату. Откройте раздел и нажмите «Сверить с банком».';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Счёт')
                    ->formatStateUsing(fn (mixed $state): string => '№ '.$state)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                FinanceTable::userColumn('Плательщик'),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Сумма')
                    ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state))
                    ->weight(FontWeight::SemiBold)
                    ->alignEnd()
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Оплачено')
                            ->query(fn (QueryBuilder $query): QueryBuilder => $query->where('status', BalanceTopup::STATUS_PAID))
                            ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state)),
                    ),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (BalanceTopup $record): string => $record->isOverdue()
                        ? 'Завис: банк не ответил'
                        : $record->statusLabel())
                    ->color(fn (BalanceTopup $record): string => $record->isOverdue() ? 'danger' : $record->statusColor())
                    ->icon(fn (BalanceTopup $record): string => match (true) {
                        $record->isOverdue() => 'heroicon-m-exclamation-triangle',
                        $record->status === BalanceTopup::STATUS_PAID => 'heroicon-m-check-circle',
                        $record->status === BalanceTopup::STATUS_FAILED => 'heroicon-m-x-circle',
                        $record->isPending() => 'heroicon-m-clock',
                        default => 'heroicon-m-minus-circle',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_test')
                    ->label('Режим')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Тест' : 'Боевой')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('card_last_digits')
                    ->label('Карта')
                    ->formatStateUsing(fn (string $state): string => '•••• '.$state)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('rrn')
                    ->label('RRN')
                    ->copyable()
                    ->copyMessage('RRN скопирован')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Оплачен')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('bank_state')
                    ->label('Ответ банка')
                    ->formatStateUsing(fn (BalanceTopup $record): string => $record->bankStateLabel())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->multiple()
                    ->options([
                        BalanceTopup::STATUS_PAID => 'Оплачен',
                        BalanceTopup::STATUS_PENDING => 'Ожидает оплаты',
                        BalanceTopup::STATUS_FAILED => 'Ошибка оплаты',
                        BalanceTopup::STATUS_CANCELLED => 'Отменён',
                        BalanceTopup::STATUS_EXPIRED => 'Просрочен',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->label('Зависшие: нужна сверка с банком')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', BalanceTopup::STATUS_PENDING)
                        ->where('expires_at', '<', now())),
                Tables\Filters\TernaryFilter::make('is_test')
                    ->label('Режим')
                    ->placeholder('Все платежи')
                    ->trueLabel('Только тестовые')
                    ->falseLabel('Только боевые'),
                FinanceTable::userFilter(),
                FinanceTable::periodFilter('created_at', 'Создан'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Открыть'),
                static::reconcileAction(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Платёж')
                ->icon('heroicon-o-credit-card')
                ->schema([
                    Infolists\Components\TextEntry::make('id')
                        ->label('Номер счёта')
                        ->formatStateUsing(fn (mixed $state): string => '№ '.$state)
                        ->copyable(),
                    Infolists\Components\TextEntry::make('amount_cents')
                        ->label('Сумма')
                        ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state))
                        ->size(TextEntrySize::Large)
                        ->weight(FontWeight::Bold),
                    Infolists\Components\TextEntry::make('status')
                        ->label('Статус')
                        ->badge()
                        ->formatStateUsing(fn (BalanceTopup $record): string => $record->isOverdue()
                            ? 'Завис: банк не ответил'
                            : $record->statusLabel())
                        ->color(fn (BalanceTopup $record): string => $record->isOverdue() ? 'danger' : $record->statusColor()),
                    Infolists\Components\TextEntry::make('is_test')
                        ->label('Режим')
                        ->badge()
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Тестовый платёж: деньги не настоящие' : 'Боевой платёж')
                        ->color(fn (bool $state): string => $state ? 'warning' : 'success'),
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Счёт создан')
                        ->dateTime('d.m.Y H:i:s'),
                    Infolists\Components\TextEntry::make('expires_at')
                        ->label('Счёт действует до')
                        ->dateTime('d.m.Y H:i:s')
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('paid_at')
                        ->label('Оплачен')
                        ->dateTime('d.m.Y H:i:s')
                        ->placeholder('Не оплачен'),
                ])
                ->columns(['default' => 1, 'md' => 3]),

            Infolists\Components\Section::make('Плательщик')
                ->icon('heroicon-o-user')
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')
                        ->label('Имя')
                        ->placeholder('Пользователь удалён')
                        ->url(fn (BalanceTopup $record): ?string => $record->user
                            ? UserResource::getUrl('edit', ['record' => $record->user])
                            : null)
                        ->color('primary'),
                    Infolists\Components\TextEntry::make('user.email')
                        ->label('Email')
                        ->copyable()
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('user.phone')
                        ->label('Телефон')
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('user.balance')
                        ->label('Баланс сейчас')
                        ->formatStateUsing(fn (mixed $state): string => Money::rup(Money::fromDecimal($state)))
                        ->placeholder('—'),
                ])
                ->columns(['default' => 1, 'md' => 4]),

            Infolists\Components\Section::make('Данные банка')
                ->icon('heroicon-o-building-library')
                ->description('Эти данные нужны, чтобы найти платёж в личном кабинете банка или передать его в поддержку банка.')
                ->schema([
                    Infolists\Components\TextEntry::make('rrn')
                        ->label('RRN (номер операции)')
                        ->copyable()
                        ->placeholder('Появится после оплаты'),
                    Infolists\Components\TextEntry::make('card_last_digits')
                        ->label('Карта')
                        ->formatStateUsing(fn (string $state): string => '•••• '.$state)
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('bank_state')
                        ->label('Что ответил банк')
                        ->state(fn (BalanceTopup $record): string => $record->bankStateLabel()),
                    Infolists\Components\TextEntry::make('currency')
                        ->label('Код валюты счёта'),
                ])
                ->columns(['default' => 1, 'md' => 4]),

            Infolists\Components\Section::make('Зачисление на баланс')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Infolists\Components\TextEntry::make('ledgerEntry.description')
                        ->label('Запись в журнале баланса')
                        ->placeholder(fn (BalanceTopup $record): string => $record->isPending()
                            ? 'Деньги ещё не зачислены: ждём подтверждения банка'
                            : 'Деньги не зачислены'),
                    Infolists\Components\TextEntry::make('ledgerEntry.amount_cents')
                        ->label('Зачислено')
                        ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state, true))
                        ->color('success')
                        ->visible(fn (BalanceTopup $record): bool => $record->ledgerEntry !== null),
                    Infolists\Components\TextEntry::make('ledgerEntry.balance_after_cents')
                        ->label('Баланс после зачисления')
                        ->formatStateUsing(fn (mixed $state): string => Money::rup((int) $state))
                        ->visible(fn (BalanceTopup $record): bool => $record->ledgerEntry !== null),
                ])
                ->columns(['default' => 1, 'md' => 3]),
        ]);
    }

    /** Кнопка запрашивает у банка актуальное состояние счёта; деньги зачисляются только по ответу банка. */
    public static function reconcileAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('reconcile')
            ->label('Сверить с банком')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (BalanceTopup $record): bool => $record->isPending())
            ->requiresConfirmation()
            ->modalHeading('Сверить платёж с банком')
            ->modalDescription('Запросим у банка состояние счёта. Если оплата прошла, деньги будут зачислены на баланс один раз, повторная сверка ничего не задвоит.')
            ->action(fn (BalanceTopup $record) => static::reconcile($record));
    }

    public static function reconcile(BalanceTopup $topup): BalanceTopup
    {
        $service = app(BalanceTopupService::class);

        if (! $service->isAvailable()) {
            Notification::make()
                ->title('Банковский шлюз не настроен')
                ->body('Проверьте настройки WEBPAYMENT_* в .env: без них сверка невозможна.')
                ->danger()
                ->send();

            return $topup;
        }

        $topup = $service->settle($topup)->refresh();

        match (true) {
            $topup->status === BalanceTopup::STATUS_PAID => Notification::make()
                ->title('Платёж подтверждён банком')
                ->body('На баланс пользователя зачислено '.Money::rup($topup->amount_cents).'.')
                ->success()
                ->send(),
            $topup->isPending() => Notification::make()
                ->title('Банк пока не подтвердил оплату')
                ->body('Статус не изменился. Если покупатель ещё платит, подождите; если счёт завис, повторите сверку позже.')
                ->warning()
                ->send(),
            default => Notification::make()
                ->title('Платёж закрыт: '.mb_strtolower($topup->statusLabel()))
                ->body('Банк ответил: '.mb_strtolower($topup->bankStateLabel()).'. Деньги на баланс не зачислены, пользователь уведомлён.')
                ->info()
                ->send(),
        };

        return $topup;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'ledgerEntry']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBalanceTopups::route('/'),
            'view' => Pages\ViewBalanceTopup::route('/{record}'),
        ];
    }
}
