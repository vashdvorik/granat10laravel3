<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegionResource\Pages;
use App\Models\Region;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Управление';
    protected static ?string $navigationLabel = 'Регионы';
    protected static ?string $modelLabel = 'регион';
    protected static ?string $pluralModelLabel = 'регионы';
    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Название')->required()->maxLength(255),
            Forms\Components\TextInput::make('type')->label('Тип')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->alphaDash()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('sort_order')
                ->label('Сортировка')
                ->integer()
                ->minValue(0)
                ->default(0)
                ->required(),
            Forms\Components\Toggle::make('is_active')->label('Активен')->default(true),
            Forms\Components\TextInput::make('meta_title')
                ->label('SEO title')
                ->maxLength(255)
                ->columnSpanFull(),
            Forms\Components\Textarea::make('meta_description')
                ->label('SEO description')
                ->rows(4)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Название')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Тип')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->searchable(),
                Tables\Columns\TextColumn::make('settlements_count')
                    ->label('Населённые пункты')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->label('Сортировка')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Активен')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('deleteRegion')
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Region $record, Tables\Actions\Action $action): void {
                        try {
                            $record->delete();
                        } catch (LogicException $exception) {
                            Notification::make()
                                ->title('Регион не удалён')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                            $action->halt();
                        }

                        Notification::make()->title('Регион удалён')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('settlements');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit' => Pages\EditRegion::route('/{record}/edit'),
        ];
    }
}
