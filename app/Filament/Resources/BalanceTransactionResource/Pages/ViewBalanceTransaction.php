<?php

namespace App\Filament\Resources\BalanceTransactionResource\Pages;

use App\Filament\Resources\BalanceTransactionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewBalanceTransaction extends ViewRecord
{
    protected static string $resource = BalanceTransactionResource::class;

    public function getTitle(): string
    {
        return 'Операция № '.$this->getRecord()->getKey();
    }
}
