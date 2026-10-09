<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Support\CategoryPicker;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

/**
 * Категории. Список и управление деревом — одна страница `ManageCategories` (дерево с раскрытием, поиском, переносом),
 * а здесь форма полной правки: название, адрес, иконка, цвета, описание.
 */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Управление';

    protected static ?string $navigationLabel = 'Категории';

    protected static ?string $modelLabel = 'категория';

    protected static ?string $pluralModelLabel = 'категории';

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('alias')
                    ->label('Адрес (alias)')
                    ->helperText('Часть ссылки на категорию, латиницей. При создании можно оставить пустым: адрес создастся из названия.')
                    ->required(fn (string $operation): bool => $operation === 'edit')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('parent_id')
                    ->label('Родительская категория')
                    ->helperText('Начните вводить название. Пусто — корневая категория. Переносить категории удобнее в дереве: меню «⋮» → «Переместить».')
                    ->searchable()
                    ->options(fn (?Category $record): array => CategoryPicker::parents('', $record))
                    ->getSearchResultsUsing(fn (string $search, ?Category $record): array => CategoryPicker::parents($search, $record))
                    ->getOptionLabelUsing(fn ($value): ?string => CategoryPicker::path($value))
                    ->placeholder('— Корневая категория —'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Показывать в каталоге')
                    ->default(true),
                Forms\Components\TextInput::make('icon')
                    ->label('Иконка')
                    ->maxLength(255),
                Forms\Components\ColorPicker::make('icon_color')
                    ->label('Цвет иконки')
                    ->default('#696969'),
                Forms\Components\ColorPicker::make('bg_color')
                    ->label('Цвет фона')
                    ->default('#e6e6e6'),
                Forms\Components\Textarea::make('desc')
                    ->label('Описание')
                    ->rows(5)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
