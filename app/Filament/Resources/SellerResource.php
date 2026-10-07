<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SellerResource\Pages;
use App\Filament\Resources\SellerResource\RelationManagers\ProductsRelationManager;
use App\Models\Seller;
use App\Models\Settlement;
use App\Models\User;
use App\Services\AdminSellerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SellerResource extends Resource
{
    protected static ?string $model = Seller::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Управление';

    protected static ?string $navigationLabel = 'Продавцы';

    protected static ?string $modelLabel = 'магазин';

    protected static ?string $pluralModelLabel = 'продавцы';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Владелец')
                            ->options(fn (?Seller $record): array => User::query()
                                ->where(function (Builder $query) use ($record): void {
                                    $query
                                        ->whereDoesntHave('seller')
                                        ->when(
                                            $record,
                                            fn (Builder $query): Builder => $query->orWhere('id', $record->user_id),
                                        );
                                })
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (User $user): array => [
                                    $user->getKey() => "{$user->name} ({$user->email})",
                                ])
                                ->all())
                            ->searchable()
                            ->required()
                            ->unique(table: Seller::class, column: 'user_id', ignoreRecord: true)
                            ->disabledOn('edit')
                            ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(60)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('alias')
                            ->label('Alias')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Forms\Components\FileUpload::make('logo')
                            ->label('Логотип')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->storeFiles(false),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\Select::make('settlement_id')
                            ->label('Населённый пункт')
                            ->options(fn (): array => Settlement::query()
                                ->active()
                                ->ordered()
                                ->with('region')
                                ->get()
                                ->groupBy(fn (Settlement $settlement): string => $settlement->region->name)
                                ->map(fn ($settlements): array => $settlements
                                    ->mapWithKeys(fn (Settlement $settlement): array => [
                                        $settlement->id => "{$settlement->name} ({$settlement->type})",
                                    ])
                                    ->all())
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('sellerplace')
                            ->label('Район / торговое место')
                            ->maxLength(60),
                        Forms\Components\Textarea::make('address')
                            ->label('Адрес')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(5)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Ссылки и контакты')
                    ->schema([
                        Forms\Components\TextInput::make('link')
                            ->label('Сайт')
                            ->url()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('link_map')
                            ->label('Ссылка на карту')
                            ->url()
                            ->maxLength(2000),
                        Forms\Components\TextInput::make('telegram')
                            ->label('Telegram')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('instagram')
                            ->label('Instagram')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('viber')
                            ->label('Viber')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Логотип')
                    ->disk('public')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Владелец')
                    ->description(fn (Seller $record): ?string => $record->user?->name)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('alias')
                    ->label('Alias')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('settlement.name')
                    ->label('Населённый пункт')
                    ->description(fn (Seller $record): ?string => $record->settlement?->region?->name)
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('active_products_count')
                    ->label('Активные товары')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('without_settlement')
                    ->label('Населённый пункт')
                    ->placeholder('Все продавцы')
                    ->trueLabel('Без населённого пункта')
                    ->falseLabel('С населённым пунктом')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('settlement_id'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('settlement_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('publicPage')
                    ->label('Открыть магазин')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Seller $record): string => route('seller', $record->alias))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('products')
                    ->label('Товары')
                    ->icon('heroicon-o-shopping-bag')
                    ->url(fn (Seller $record): string => static::getUrl('edit', [
                        'record' => $record,
                        'activeRelationManager' => 0,
                    ])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('deleteSeller')
                    ->label('Удалить магазин')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Магазин, его товары, изображения будут удалены без возможности восстановления.')
                    ->action(function (Seller $record, AdminSellerService $service): void {
                        $service->delete(auth()->user(), $record);

                        Notification::make()
                            ->title('Магазин удалён')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user'])
            ->withCount([
                'products as active_products_count' => fn (Builder $query): Builder => $query
                    ->where('is_active', true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSellers::route('/'),
            'create' => Pages\CreateSeller::route('/create'),
            'edit' => Pages\EditSeller::route('/{record}/edit'),
        ];
    }
}
