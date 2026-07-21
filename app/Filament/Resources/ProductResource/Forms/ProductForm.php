<?php

namespace App\Filament\Resources\ProductResource\Forms;

use App\Models\Category;
use App\Models\Seller;
use Filament\Forms;
use Filament\Forms\Form;

class ProductForm
{
    public static function configure(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')
                ->schema([
                    Forms\Components\Select::make('seller_id')
                        ->label('Продавец')
                        ->options(fn (): array => Seller::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->required()->disabledOn('edit')
                        ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                    Forms\Components\Select::make('category_id')
                        ->label('Конечная категория')
                        ->options(fn (): array => Category::query()
                            ->whereDoesntHave('children')->defaultOrder()->pluck('name', 'id')->all())
                        ->searchable()->required(),
                    Forms\Components\TextInput::make('name')
                        ->label('Название')->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\TextInput::make('alias')
                        ->label('Alias')->disabled()->dehydrated(false)->visibleOn('edit'),
                    Forms\Components\TextInput::make('price')
                        ->label('Цена, RUP')->numeric()->minValue(0)->step(0.01)->required(),
                    Forms\Components\TextInput::make('lowprice')
                        ->label('Цена со скидкой, RUP')->numeric()->minValue(0)->step(0.01)->lt('price'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Опубликовать после сохранения')
                        ->helperText('Товар станет виден покупателям только после успешной модерации.')
                        ->default(false),
                    Forms\Components\Toggle::make('is_vip')->label('VIP')->default(false),
                    Forms\Components\Textarea::make('desc')
                        ->label('Описание')->rows(6)->columnSpanFull(),
                ])->columns(2),
            Forms\Components\Section::make('Характеристики')
                ->schema([
                    Forms\Components\TextInput::make('features1')->label('Характеристика 1')->maxLength(255),
                    Forms\Components\TextInput::make('features2')->label('Характеристика 2')->maxLength(255),
                    Forms\Components\TextInput::make('features3')->label('Характеристика 3')->maxLength(255),
                    Forms\Components\TextInput::make('features4')->label('Характеристика 4')->maxLength(255),
                ])->columns(2),
            Forms\Components\Section::make('Новые изображения')
                ->description('Максимум пять изображений на товар. При редактировании файлы добавляются к существующим.')
                ->schema([
                    Forms\Components\FileUpload::make('images')
                        ->label('Изображения')->multiple()->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(5120)->maxFiles(5)->storeFiles(false)->dehydrated(true)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
