<?php

namespace App\Filament\Resources;

use App\Exceptions\ProductActivationException;
use App\Exceptions\ProductPushTooSoonException;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\ImagesRelationManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Services\ProductActionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Управление';

    protected static ?string $navigationLabel = 'Товары';

    protected static ?string $modelLabel = 'товар';

    protected static ?string $pluralModelLabel = 'товары';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('seller_id')
                            ->label('Продавец')
                            ->options(fn (): array => Seller::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->disabledOn('edit')
                            ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                        Forms\Components\Select::make('category_id')
                            ->label('Конечная категория')
                            ->options(fn (): array => Category::query()
                                ->whereDoesntHave('children')
                                ->defaultOrder()
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('alias')
                            ->label('Alias')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Forms\Components\TextInput::make('price')
                            ->label('Цена, RUP')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required(),
                        Forms\Components\TextInput::make('lowprice')
                            ->label('Цена со скидкой, RUP')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->lt('price'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Опубликован')
                            ->default(false),
                        Forms\Components\Toggle::make('is_vip')
                            ->label('VIP')
                            ->default(false),
                        Forms\Components\Textarea::make('desc')
                            ->label('Описание')
                            ->rows(6)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Характеристики')
                    ->schema([
                        Forms\Components\TextInput::make('features1')
                            ->label('Характеристика 1')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('features2')
                            ->label('Характеристика 2')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('features3')
                            ->label('Характеристика 3')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('features4')
                            ->label('Характеристика 4')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Новые изображения')
                    ->description('Максимум пять изображений на товар. При редактировании файлы добавляются к существующим.')
                    ->schema([
                        Forms\Components\FileUpload::make('images')
                            ->label('Изображения')
                            ->multiple()
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->maxFiles(5)
                            ->storeFiles(false)
                            ->dehydrated(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('mainImage.path')
                    ->label('Фото')
                    ->disk('public')
                    ->square(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(['name', 'alias'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('seller.name')
                    ->label('Продавец')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Категория')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2, ',', ' ').' RUP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lowprice')
                    ->label('Скидка')
                    ->formatStateUsing(fn (mixed $state): string => filled($state)
                        ? number_format((float) $state, 2, ',', ' ').' RUP'
                        : '—')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_vip')
                    ->label('VIP')
                    ->boolean(),
                Tables\Columns\TextColumn::make('images_count')
                    ->label('Фото')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('push_at')
                    ->label('Поднят')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('seller_id')
                    ->label('Продавец')
                    ->relationship('seller', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Категория')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность'),
                Tables\Filters\TernaryFilter::make('is_vip')
                    ->label('VIP'),
                Tables\Filters\TernaryFilter::make('on_sale')
                    ->label('Со скидкой')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('lowprice'),
                        false: fn (Builder $query): Builder => $query->whereNull('lowprice'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('publicPage')
                    ->label('Открыть')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Product $record): string => route('product.show', $record->alias))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('activate')
                    ->label('Опубликовать')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->authorize('activate')
                    ->visible(fn (Product $record): bool => ! (bool) $record->is_active)
                    ->requiresConfirmation()
                    ->action(function (
                        Product $record,
                        ProductActionService $service,
                        Tables\Actions\Action $action
                    ): void {
                        try {
                            $service->activate($record);
                        } catch (ProductActivationException $exception) {
                            Notification::make()
                                ->title('Товар не опубликован')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            $action->halt();
                        }

                        Notification::make()
                            ->title('Товар опубликован')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('deactivate')
                    ->label('Снять с публикации')
                    ->icon('heroicon-o-eye-slash')
                    ->authorize('deactivate')
                    ->visible(fn (Product $record): bool => (bool) $record->is_active)
                    ->requiresConfirmation()
                    ->action(function (Product $record, ProductActionService $service): void {
                        $service->deactivate($record);

                        Notification::make()
                            ->title('Товар снят с публикации')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('pushUp')
                    ->label('Поднять')
                    ->icon('heroicon-o-arrow-up')
                    ->authorize('pushUp')
                    ->requiresConfirmation()
                    ->action(function (
                        Product $record,
                        ProductActionService $service,
                        Tables\Actions\Action $action
                    ): void {
                        try {
                            $service->pushUp($record);
                        } catch (ProductPushTooSoonException $exception) {
                            Notification::make()
                                ->title('Товар пока нельзя поднять')
                                ->body($exception->getMessage())
                                ->warning()
                                ->send();

                            $action->halt();
                        }

                        Notification::make()
                            ->title('Товар поднят')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('deleteProduct')
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->authorize('delete')
                    ->requiresConfirmation()
                    ->action(function (Product $record, ProductActionService $service): void {
                        $service->delete($record);

                        Notification::make()
                            ->title('Товар удалён')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['seller', 'category', 'mainImage'])
            ->withCount('images');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
