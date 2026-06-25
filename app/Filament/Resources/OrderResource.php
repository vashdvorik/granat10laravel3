<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\StatusHistoryRelationManager;
use App\Models\Order;
use App\Services\OrderManagementService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Управление';

    protected static ?string $navigationLabel = 'Заказы';

    protected static ?string $modelLabel = 'заказ';

    protected static ?string $pluralModelLabel = 'заказы';

    protected static ?int $navigationSort = 35;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Заказ')
                ->schema([
                    Forms\Components\TextInput::make('number')->label('Номер')->disabled(),
                    Forms\Components\TextInput::make('status')
                        ->label('Статус')
                        ->formatStateUsing(fn (OrderStatus|string $state): string => ($state instanceof OrderStatus
                            ? $state
                            : OrderStatus::from($state))->label())
                        ->disabled(),
                    Forms\Components\TextInput::make('product_name')->label('Товар')->disabled(),
                    Forms\Components\TextInput::make('quantity')->label('Количество')->disabled(),
                    Forms\Components\TextInput::make('unit_price')->label('Цена')->suffix(' руб.')->disabled(),
                    Forms\Components\TextInput::make('seller_name')->label('Магазин')->disabled(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Покупатель')
                ->schema([
                    Forms\Components\TextInput::make('customer_name')->label('Имя')->disabled(),
                    Forms\Components\TextInput::make('customer_phone')->label('Телефон')->disabled(),
                    Forms\Components\TextInput::make('fulfillment_method')
                        ->label('Получение')
                        ->formatStateUsing(fn (string $state): string => $state === 'delivery' ? 'Доставка' : 'Самовывоз')
                        ->disabled(),
                    Forms\Components\Textarea::make('delivery_address')
                        ->label('Адрес доставки')
                        ->visible(fn (?Order $record): bool => $record?->isDelivery() ?? false)
                        ->disabled()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('customer_comment')
                        ->label('Комментарий покупателя')
                        ->disabled()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Номер')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('seller.name')
                    ->label('Магазин')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Товар')
                    ->searchable()
                    ->limit(35),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Покупатель')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Сумма')
                    ->state(fn (Order $record): float => (float) $record->unit_price * $record->quantity)
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2, ',', ' ').' RUP'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(collect(OrderStatus::cases())
                        ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                Tables\Filters\SelectFilter::make('seller_id')
                    ->label('Магазин')
                    ->relationship('seller', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                static::statusAction(),
            ])
            ->bulkActions([]);
    }

    public static function statusAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('changeStatus')
            ->label('Изменить статус')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn (Order $record): bool => ! $record->isFinal())
            ->form([
                Forms\Components\Select::make('status')
                    ->label('Новый статус')
                    ->options(fn (Order $record): array => collect($record->status->allowedTransitions())
                        ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $status->label()])
                        ->all())
                    ->required(),
                Forms\Components\Textarea::make('comment')
                    ->label('Комментарий')
                    ->maxLength(1000),
            ])
            ->action(function (Order $record, array $data, OrderManagementService $service): void {
                $service->transition(
                    $record,
                    OrderStatus::from($data['status']),
                    auth()->user(),
                    $data['comment'] ?? null,
                );

                Notification::make()
                    ->title('Статус заказа обновлён')
                    ->success()
                    ->send();
            });
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('seller');
    }

    public static function getRelations(): array
    {
        return [
            StatusHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
