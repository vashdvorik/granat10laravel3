<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SellerPackageResource\Pages;
use App\Models\Package;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Services\AdminPackageAssignmentService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SellerPackageResource extends Resource
{
    protected static ?string $model = SellerPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Управление';

    protected static ?string $navigationLabel = 'Подписки';

    protected static ?string $modelLabel = 'подписка';

    protected static ?string $pluralModelLabel = 'подписки';

    protected static ?int $navigationSort = 51;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('seller.name')
                    ->label('Продавец')
                    ->description(fn (SellerPackage $record): ?string => $record->seller->user?->email)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('package.name')
                    ->label('Тариф')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Начало')
                    ->date('d.m.Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Окончание')
                    ->date('d.m.Y')
                    ->placeholder('Бессрочно')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('seller_id')
                    ->label('Продавец')
                    ->relationship('seller', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('package_id')
                    ->label('Тариф')
                    ->relationship('package', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('assignPackage')
                    ->label('Назначить тариф')
                    ->icon('heroicon-o-plus')
                    ->form(static::assignmentForm())
                    ->action(function (
                        array $data,
                        AdminPackageAssignmentService $service,
                        Tables\Actions\Action $action
                    ): void {
                        static::runAction($action, function () use ($data, $service): void {
                            $service->assign(
                                actor: auth()->user(),
                                seller: Seller::query()->findOrFail($data['seller_id']),
                                package: Package::query()->findOrFail($data['package_id']),
                                chargeBalance: (bool) ($data['charge_balance'] ?? false),
                                reason: $data['reason'],
                            );
                        }, 'Тариф назначен');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('renewPackage')
                    ->label('Продлить')
                    ->icon('heroicon-o-arrow-path')
                    ->form(static::operationForm())
                    ->action(function (
                        SellerPackage $record,
                        array $data,
                        AdminPackageAssignmentService $service,
                        Tables\Actions\Action $action
                    ): void {
                        static::runAction($action, function () use ($record, $data, $service): void {
                            $service->renew(
                                actor: auth()->user(),
                                sellerPackage: $record,
                                chargeBalance: (bool) ($data['charge_balance'] ?? false),
                                reason: $data['reason'],
                            );
                        }, 'Тариф продлён');
                    }),
                Tables\Actions\Action::make('deactivatePackage')
                    ->label('Деактивировать')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SellerPackage $record): bool => $record->is_active)
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Причина')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (
                        SellerPackage $record,
                        array $data,
                        AdminPackageAssignmentService $service,
                        Tables\Actions\Action $action
                    ): void {
                        static::runAction($action, function () use ($record, $data, $service): void {
                            $service->deactivate(auth()->user(), $record, $data['reason']);
                        }, 'Тариф деактивирован');
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['seller.user', 'package']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSellerPackages::route('/'),
        ];
    }

    private static function assignmentForm(): array
    {
        return [
            Forms\Components\Select::make('seller_id')
                ->label('Продавец')
                ->options(fn (): array => Seller::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->required(),
            Forms\Components\Select::make('package_id')
                ->label('Тариф')
                ->options(fn (): array => Package::query()
                    ->where('is_active', true)
                    ->orderBy('price')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->required(),
            ...static::operationForm(),
        ];
    }

    private static function operationForm(): array
    {
        return [
            Forms\Components\Toggle::make('charge_balance')
                ->label('Списать стоимость с баланса владельца')
                ->default(false),
            Forms\Components\Textarea::make('reason')
                ->label('Причина')
                ->required()
                ->maxLength(1000),
        ];
    }

    private static function runAction(
        Tables\Actions\Action $action,
        callable $callback,
        string $successTitle
    ): void {
        try {
            $callback();
        } catch (ValidationException|RuntimeException $exception) {
            $message = $exception instanceof ValidationException
                ? collect($exception->errors())->flatten()->first()
                : $exception->getMessage();

            Notification::make()
                ->title('Операция не выполнена')
                ->body($message)
                ->danger()
                ->send();

            $action->halt();
        }

        Notification::make()
            ->title($successTitle)
            ->success()
            ->send();
    }
}
