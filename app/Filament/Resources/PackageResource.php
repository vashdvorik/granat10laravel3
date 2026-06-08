<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Models\Package;
use App\Services\AdminPackageService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Управление';

    protected static ?string $navigationLabel = 'Тарифы';

    protected static ?string $modelLabel = 'тариф';

    protected static ?string $pluralModelLabel = 'тарифы';

    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->label('Цена')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix('RUP')
                    ->required(),
                Forms\Components\TextInput::make('duration_days')
                    ->label('Срок действия, дней')
                    ->integer()
                    ->minValue(1)
                    ->nullable()
                    ->helperText('Оставьте пустым для бессрочного тарифа.'),
                Forms\Components\TextInput::make('active_products_limit')
                    ->label('Лимит активных товаров')
                    ->integer()
                    ->minValue(0)
                    ->nullable()
                    ->helperText('0 — публикация запрещена, пустое значение — без ограничений.'),
                Forms\Components\TextInput::make('vip_products_limit')
                    ->label('Лимит VIP-товаров')
                    ->integer()
                    ->minValue(0)
                    ->nullable()
                    ->helperText('0 — VIP запрещён, пустое значение — без ограничений.'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Доступен для назначения')
                    ->default(true),
                Forms\Components\Section::make('Возможности')
                    ->schema([
                        Forms\Components\Toggle::make('has_website_link')
                            ->label('Ссылка на сайт'),
                        Forms\Components\Toggle::make('has_social_links')
                            ->label('Социальные ссылки'),
                        Forms\Components\Toggle::make('has_order_system')
                            ->label('Система заказов'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2, '.', ' ').' RUP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Срок')
                    ->formatStateUsing(fn (mixed $state): string => "{$state} дней")
                    ->placeholder('Бессрочно'),
                Tables\Columns\TextColumn::make('active_products_limit')
                    ->label('Активные товары')
                    ->formatStateUsing(fn (mixed $state): string => static::formatLimit($state))
                    ->placeholder('Без ограничений'),
                Tables\Columns\TextColumn::make('vip_products_limit')
                    ->label('VIP')
                    ->formatStateUsing(fn (mixed $state): string => static::formatLimit($state))
                    ->placeholder('Без ограничений'),
                Tables\Columns\TextColumn::make('seller_packages_count')
                    ->label('Подписки')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
            ])
            ->defaultSort('price')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('deletePackage')
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (
                        Package $record,
                        AdminPackageService $service,
                        Tables\Actions\Action $action
                    ): void {
                        try {
                            $service->delete(auth()->user(), $record);
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('Тариф не удалён')
                                ->body(collect($exception->errors())->flatten()->first())
                                ->danger()
                                ->send();

                            $action->halt();
                        }

                        Notification::make()
                            ->title('Тариф удалён')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('sellerPackages');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }

    private static function formatLimit(mixed $state): string
    {
        if ($state === null) {
            return 'Без ограничений';
        }

        if ((int) $state === 0) {
            return 'Запрещено';
        }

        return (string) $state;
    }
}
