<?php

namespace App\Filament\Resources\SellerResource\Pages;

use App\Filament\Resources\SellerResource;
use App\Models\User;
use App\Services\ShopService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class CreateSeller extends CreateRecord
{
    protected static string $resource = SellerResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::query()->findOrFail($data['user_id']);
        $logo = $data['logo'] ?? null;

        unset($data['user_id'], $data['logo']);

        return app(ShopService::class)->createShop(
            $user,
            $data,
            $logo instanceof UploadedFile ? $logo : null,
        );
    }
}
