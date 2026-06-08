<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Services\CategoryTreeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

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
                    ->label('Alias')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('number')
                    ->label('Номер сортировки')
                    ->integer()
                    ->default(0)
                    ->required(),
                Forms\Components\Select::make('parent_id')
                    ->label('Родительская категория')
                    ->options(fn (?Category $record): array => static::parentOptions($record))
                    ->searchable()
                    ->placeholder('Корневая категория'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активна')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->description(fn (Category $record): string => $record->ancestors
                        ->pluck('name')
                        ->push($record->name)
                        ->implode(' → '))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('alias')
                    ->label('Alias')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Родитель')
                    ->placeholder('Корневая'),
                Tables\Columns\TextColumn::make('number')
                    ->label('Сортировка')
                    ->sortable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Товары')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('children_count')
                    ->label('Дочерние')
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
            ])
            ->defaultSort('_lft')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('deleteCategory')
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (
                        Category $record,
                        CategoryTreeService $service,
                        Tables\Actions\Action $action
                    ): void {
                        try {
                            $service->delete(auth()->user(), $record);
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('Категория не удалена')
                                ->body(collect($exception->errors())->flatten()->first())
                                ->danger()
                                ->send();

                            $action->halt();
                        }

                        Notification::make()
                            ->title('Категория удалена')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['parent', 'ancestors'])
            ->withCount(['products', 'children']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    private static function parentOptions(?Category $record): array
    {
        $excludedIds = $record
            ? Category::query()->descendantsAndSelf($record->getKey())->pluck('id')->all()
            : [];

        return Category::query()
            ->when($excludedIds !== [], fn (Builder $query): Builder => $query->whereNotIn('id', $excludedIds))
            ->withDepth()
            ->defaultOrder()
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $category->getKey() => str_repeat('— ', max(0, (int) $category->depth)).$category->name,
            ])
            ->all();
    }
}
