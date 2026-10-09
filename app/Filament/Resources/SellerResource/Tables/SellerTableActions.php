<?php

namespace App\Filament\Resources\SellerResource\Tables;

use App\Filament\Resources\SellerResource;
use App\Models\Seller;
use App\Services\AdminSellerService;
use App\Services\SellerVisibilityService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;

/** Все действия с продавцом в одной кнопке «Действия»: таблица не уходит вправо, как и в списке товаров. */
class SellerTableActions
{
    public static function make(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                self::publicPage(),
                self::products(),
                Tables\Actions\EditAction::make(),
                self::hideSeller(),
                self::changeReason(),
                self::showSeller(),
                self::deleteSeller(),
            ])->label('Действия')->icon('heroicon-m-ellipsis-vertical')->button(),
        ];
    }

    /** Поле причины одинаково в списке и на странице редактирования. */
    public static function reasonForm(): array
    {
        return [
            Forms\Components\Textarea::make('reason')
                ->label('Причина для продавца')
                ->helperText('Этот текст продавец увидит красным уведомлением в своём кабинете. '
                    .'Напишите коротко и по делу, что именно не так и что исправить.')
                ->placeholder('Например: название магазина нарушает правила площадки. Измените название и напишите в поддержку.')
                ->required()
                ->rows(4)
                ->maxLength(SellerVisibilityService::MAX_REASON_LENGTH),
        ];
    }

    private static function publicPage(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('publicPage')
            ->label('Открыть магазин')->icon('heroicon-o-arrow-top-right-on-square')
            ->visible(fn (Seller $record): bool => ! $record->isHidden())
            ->url(fn (Seller $record): string => route('seller', $record->alias))
            ->openUrlInNewTab();
    }

    private static function products(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('products')
            ->label('Товары')->icon('heroicon-o-shopping-bag')
            ->url(fn (Seller $record): string => SellerResource::getUrl('edit', [
                'record' => $record,
                'activeRelationManager' => 0,
            ]));
    }

    private static function hideSeller(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('hideSeller')
            ->label('Скрыть магазин')->icon('heroicon-o-eye-slash')->color('warning')
            ->authorize('manageVisibility')
            ->visible(fn (Seller $record): bool => ! $record->isHidden())
            ->modalHeading('Скрыть магазин и его товары')
            ->modalDescription('Магазин и все его товары пропадут с сайта: из каталога, поиска и карты сайта. '
                .'Данные не удаляются, продавец по-прежнему входит в кабинет.')
            ->modalSubmitActionLabel('Скрыть магазин')
            ->form(self::reasonForm())
            ->action(function (Seller $record, array $data, SellerVisibilityService $service): void {
                $service->hide(auth()->user(), $record, $data['reason']);

                Notification::make()
                    ->title('Магазин скрыт')
                    ->body('Продавец увидит причину красным уведомлением в кабинете.')
                    ->warning()
                    ->send();
            });
    }

    private static function changeReason(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('changeHiddenReason')
            ->label('Изменить причину')->icon('heroicon-o-pencil-square')
            ->authorize('manageVisibility')
            ->visible(fn (Seller $record): bool => $record->isHidden())
            ->modalHeading('Причина, которую видит продавец')
            ->modalSubmitActionLabel('Сохранить')
            ->fillForm(fn (Seller $record): array => ['reason' => $record->hidden_reason])
            ->form(self::reasonForm())
            ->action(function (Seller $record, array $data, SellerVisibilityService $service): void {
                $service->hide(auth()->user(), $record, $data['reason']);

                Notification::make()->title('Причина обновлена')->success()->send();
            });
    }

    private static function showSeller(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('showSeller')
            ->label('Показать магазин')->icon('heroicon-o-eye')->color('success')
            ->authorize('manageVisibility')
            ->visible(fn (Seller $record): bool => $record->isHidden())
            ->requiresConfirmation()
            ->modalHeading('Вернуть магазин на сайт')
            ->modalDescription('Магазин появится в каталоге вместе с товарами, которые опубликованы и прошли проверку. '
                .'Красное уведомление в кабинете продавца исчезнет.')
            ->action(function (Seller $record, SellerVisibilityService $service): void {
                $service->show(auth()->user(), $record);

                Notification::make()->title('Магазин снова виден покупателям')->success()->send();
            });
    }

    private static function deleteSeller(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('deleteSeller')
            ->label('Удалить магазин')->icon('heroicon-o-trash')->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Магазин, его товары, изображения будут удалены без возможности восстановления.')
            ->action(function (Seller $record, AdminSellerService $service): void {
                $service->delete(auth()->user(), $record);

                Notification::make()
                    ->title('Магазин удалён')
                    ->success()
                    ->send();
            });
    }
}
