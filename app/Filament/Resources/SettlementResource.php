<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettlementResource\Pages;
use App\Models\Settlement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

class SettlementResource extends Resource
{
    protected static ?string $model = Settlement::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Управление';
    protected static ?string $navigationLabel = 'Населённые пункты';
    protected static ?string $modelLabel = 'населённый пункт';
    protected static ?string $pluralModelLabel = 'населённые пункты';
    protected static ?int $navigationSort = 51;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('region_id')
                ->label('Регион')
                ->relationship(
                    name: 'region',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder => $query->ordered(),
                )
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('name')->label('Название')->required()->maxLength(255),
            Forms\Components\TextInput::make('type')->label('Тип')->required()->maxLength(255),
            Forms\Components\TextInput::make('zip')->label('Почтовый индекс')->maxLength(20),
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
                Tables\Columns\TextColumn::make('region.name')->label('Регион')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Тип')->searchable(),
                Tables\Columns\TextColumn::make('zip')->label('Индекс')->placeholder('—'),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->searchable(),
                Tables\Columns\TextColumn::make('sellers_count')
                    ->label('Продавцы')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->label('Сортировка')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Активен')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('region_id')
                    ->label('Регион')
                    ->relationship('region', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Активность'),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('deleteSettlement')
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Settlement $record, Tables\Actions\Action $action): void {
                        try {
                            $record->delete();
                        } catch (LogicException $exception) {
                            Notification::make()
                                ->title('Населённый пункт не удалён')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                            $action->halt();
                        }

                        Notification::make()->title('Населённый пункт удалён')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('region')->withCount('sellers');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettlements::route('/'),
            'create' => Pages\CreateSettlement::route('/create'),
            'edit' => Pages\EditSettlement::route('/{record}/edit'),
        ];
    }
}
