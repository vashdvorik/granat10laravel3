<?php

namespace App\Filament\Resources\VipPurchaseResource\Pages;

use App\Filament\Resources\VipPurchaseResource;
use Filament\Resources\Pages\ViewRecord;

class ViewVipPurchase extends ViewRecord
{
    protected static string $resource = VipPurchaseResource::class;

    public function getTitle(): string
    {
        return 'Покупка VIP № '.$this->getRecord()->getKey();
    }
}
