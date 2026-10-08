<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\VipPurchaseResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Покупки VIP, которые пользователь сделал для своих товаров. */
class VipPurchasesRelationManager extends RelationManager
{
    protected static string $relationship = 'vipPurchases';

    protected static ?string $title = 'Покупки VIP';

    public function table(Table $table): Table
    {
        return VipPurchaseResource::configureTable($table, withUser: false)
            ->recordUrl(fn ($record): string => VipPurchaseResource::getUrl('view', ['record' => $record]))
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Подробнее')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record): string => VipPurchaseResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
