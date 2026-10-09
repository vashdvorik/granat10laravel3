<?php

namespace App\Filament\Resources\SellerResource\Pages;

use App\Filament\Resources\SellerResource;
use App\Filament\Resources\SellerResource\Tables\SellerTableActions;
use App\Models\Seller;
use App\Services\AdminSellerService;
use App\Services\SellerProfileService;
use App\Services\SellerVisibilityService;
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
            Actions\Action::make('hideSeller')
                ->label('Скрыть магазин')
                ->icon('heroicon-o-eye-slash')
                ->color('warning')
                ->authorize('manageVisibility')
                ->visible(fn (): bool => ! $this->getRecord()->isHidden())
                ->modalHeading('Скрыть магазин и его товары')
                ->modalDescription('Магазин и все его товары пропадут с сайта: из каталога, поиска и карты сайта. '
                    .'Данные не удаляются, продавец по-прежнему входит в кабинет.')
                ->modalSubmitActionLabel('Скрыть магазин')
                ->form(SellerTableActions::reasonForm())
                ->action(function (array $data, SellerVisibilityService $service): void {
                    $service->hide(auth()->user(), $this->getRecord(), $data['reason']);

                    Notification::make()
                        ->title('Магазин скрыт')
                        ->body('Продавец увидит причину красным уведомлением в кабинете.')
                        ->warning()
                        ->send();
                }),
            Actions\Action::make('changeHiddenReason')
                ->label('Изменить причину')
                ->icon('heroicon-o-pencil-square')
                ->authorize('manageVisibility')
                ->visible(fn (): bool => $this->getRecord()->isHidden())
                ->modalHeading('Причина, которую видит продавец')
                ->modalSubmitActionLabel('Сохранить')
                ->fillForm(fn (): array => ['reason' => $this->getRecord()->hidden_reason])
                ->form(SellerTableActions::reasonForm())
                ->action(function (array $data, SellerVisibilityService $service): void {
                    $service->hide(auth()->user(), $this->getRecord(), $data['reason']);

                    Notification::make()->title('Причина обновлена')->success()->send();
                }),
            Actions\Action::make('showSeller')
                ->label('Показать магазин')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->authorize('manageVisibility')
                ->visible(fn (): bool => $this->getRecord()->isHidden())
                ->requiresConfirmation()
                ->modalHeading('Вернуть магазин на сайт')
                ->modalDescription('Магазин появится в каталоге вместе с товарами, которые опубликованы и прошли проверку. '
                    .'Красное уведомление в кабинете продавца исчезнет.')
                ->action(function (SellerVisibilityService $service): void {
                    $service->show(auth()->user(), $this->getRecord());

                    Notification::make()->title('Магазин снова виден покупателям')->success()->send();
                }),
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
