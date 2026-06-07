<?php

namespace App\Filament\Resources\SellerResource\Pages;

use App\Filament\Resources\SellerResource;
use App\Models\Seller;
use App\Services\AdminSellerService;
use App\Services\SellerProfileService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class EditSeller extends EditRecord
{
    protected static string $resource = SellerResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $logo = $data['logo'] ?? null;

        unset($data['logo']);

        return app(SellerProfileService::class)->updateProfile(
            $record,
            $data,
            $logo instanceof UploadedFile ? $logo : null,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('deleteSeller')
                ->label('Удалить магазин')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (AdminSellerService $service): void {
                    /** @var Seller $seller */
                    $seller = $this->getRecord();
                    $service->delete(auth()->user(), $seller);

                    Notification::make()
                        ->title('Магазин удалён')
                        ->success()
                        ->send();

                    $this->redirect(SellerResource::getUrl());
                }),
        ];
    }
}
