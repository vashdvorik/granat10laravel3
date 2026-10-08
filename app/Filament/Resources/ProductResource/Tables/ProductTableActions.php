<?php

namespace App\Filament\Resources\ProductResource\Tables;

use App\Exceptions\ProductActivationException;
use App\Exceptions\ProductModerationException;
use App\Exceptions\ProductPushTooSoonException;
use App\Models\Product;
use App\Services\ProductActionService;
use App\Services\ProductImageModerationRetryService;
use App\Services\ProductModerationRetryService;
use App\Services\ProductModerationService;
use App\Services\ProductStateTransitionService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;

class ProductTableActions
{
    public static function make(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                self::retryModeration(),
                self::retryImageModeration(),
                self::requestImageReplacement(),
                self::approveModeration(),
                self::requestChanges(),
                self::rejectModeration(),
                self::publicPage(),
                self::activate(),
                self::deactivate(),
                self::pushUp(),
                Tables\Actions\EditAction::make(),
                self::deleteProduct(),
            ])->label('Действия')->icon('heroicon-m-ellipsis-vertical')->button(),
        ];
    }

    private static function retryModeration(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('retryModeration')
            ->label('Повторить проверку')->icon('heroicon-o-arrow-path')->color('warning')
            ->visible(fn (Product $record): bool => app(ProductModerationRetryService::class)->canRetry($record))
            ->requiresConfirmation()
            ->action(function (Product $record, ProductModerationRetryService $service): void {
                $retried = $service->retry($record);
                Notification::make()
                    ->title($retried ? 'Проверка поставлена в очередь' : 'Повтор недоступен')
                    ->color($retried ? 'success' : 'danger')->send();
            });
    }

    private static function approveModeration(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('approveModeration')
            ->label('Одобрить')->icon('heroicon-o-check-circle')->color('success')
            ->visible(fn (Product $record): bool => self::canModerate($record))
            ->form([Forms\Components\Textarea::make('comment')->label('Комментарий')->maxLength(2000)])
            ->action(function (Product $record, array $data, ProductModerationService $service): void {
                $service->approve($record, auth()->user(), $data['comment'] ?? null);
                Notification::make()->title('Товар одобрен')->success()->send();
            });
    }

    private static function retryImageModeration(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('retryImageModeration')
            ->label('Повторить проверку фото')->icon('heroicon-o-photo')->color('warning')
            ->visible(fn (Product $record): bool => app(ProductImageModerationRetryService::class)->canRetry($record))
            ->requiresConfirmation()
            ->action(function (Product $record, ProductImageModerationRetryService $service): void {
                $retried = $service->retry($record);
                Notification::make()
                    ->title($retried ? 'Проверка фото поставлена в очередь' : 'Повтор проверки фото недоступен')
                    ->color($retried ? 'success' : 'danger')
                    ->send();
            });
    }

    private static function requestImageReplacement(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('requestImageReplacement')
            ->label('Запросить замену фото')->icon('heroicon-o-photo')->color('warning')
            ->visible(fn (Product $record): bool => self::canModerate($record) && $record->images()->exists())
            ->form([
                Forms\Components\Textarea::make('comment')
                    ->label('Что написать продавцу')
                    ->default('Замените проблемное фото товара и отправьте карточку на проверку повторно.')
                    ->required()
                    ->maxLength(2000),
            ])
            ->action(function (Product $record, array $data, ProductModerationService $service): void {
                $service->requestChanges($record, auth()->user(), $data['comment']);
                Notification::make()->title('Продавцу отправлен запрос заменить фото')->warning()->send();
            });
    }

    private static function requestChanges(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('requestChanges')
            ->label('На доработку')->icon('heroicon-o-pencil-square')->color('warning')
            ->visible(fn (Product $record): bool => self::canModerate($record))
            ->form([Forms\Components\Textarea::make('comment')->label('Что исправить')->required()->maxLength(2000)])
            ->action(function (Product $record, array $data, ProductModerationService $service): void {
                $service->requestChanges($record, auth()->user(), $data['comment']);
                Notification::make()->title('Товар отправлен на доработку')->warning()->send();
            });
    }

    private static function rejectModeration(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('rejectModeration')
            ->label('Отклонить')->icon('heroicon-o-x-circle')->color('danger')
            ->visible(fn (Product $record): bool => self::canModerate($record))
            ->form([
                Forms\Components\Select::make('reason_code')->label('Причина')->options([
                    'prohibited_product' => 'Запрещённый товар',
                    'fraud' => 'Мошенничество',
                    'illegal_content' => 'Незаконный контент',
                    'other' => 'Другое',
                ])->required(),
                Forms\Components\Textarea::make('comment')->label('Комментарий продавцу')->required()->maxLength(2000),
            ])
            ->action(function (Product $record, array $data, ProductModerationService $service): void {
                try {
                    $service->reject($record, auth()->user(), $data['reason_code'], $data['comment']);
                } catch (ProductModerationException $exception) {
                    Notification::make()->title('Решение не сохранено')
                        ->body($exception->getMessage())->danger()->send();

                    return;
                }
                Notification::make()->title('Товар отклонён')->danger()->send();
            });
    }

    private static function publicPage(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('publicPage')
            ->label('Открыть')->icon('heroicon-o-arrow-top-right-on-square')
            ->url(fn (Product $record): string => route('product.show', $record->alias))
            ->visible(fn (Product $record): bool => app(ProductStateTransitionService::class)->canOpenPublicPage($record))
            ->openUrlInNewTab();
    }

    private static function activate(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('activate')
            ->label('Опубликовать')->icon('heroicon-o-eye')->color('success')->authorize('activate')
            ->visible(fn (Product $record): bool => app(ProductStateTransitionService::class)->canPublish($record))
            ->requiresConfirmation()
            ->action(function (Product $record, ProductActionService $service, Tables\Actions\Action $action): void {
                try {
                    $service->activate($record);
                } catch (ProductActivationException $exception) {
                    Notification::make()->title('Товар не опубликован')
                        ->body($exception->getMessage())->danger()->send();
                    $action->halt();
                }
                Notification::make()->title('Товар опубликован')->success()->send();
            });
    }

    private static function deactivate(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('deactivate')
            ->label('Снять с публикации')->icon('heroicon-o-eye-slash')->authorize('deactivate')
            ->visible(fn (Product $record): bool => app(ProductStateTransitionService::class)->canUnpublish($record))
            ->requiresConfirmation()
            ->action(function (Product $record, ProductActionService $service): void {
                $service->deactivate($record);
                Notification::make()->title('Товар снят с публикации')->success()->send();
            });
    }

    private static function pushUp(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('pushUp')
            ->label('Поднять')->icon('heroicon-o-arrow-up')->authorize('pushUp')
            ->visible(fn (Product $record): bool => app(ProductStateTransitionService::class)->canPushUp($record))
            ->requiresConfirmation()
            ->action(function (Product $record, ProductActionService $service, Tables\Actions\Action $action): void {
                try {
                    $service->pushUp($record);
                } catch (ProductPushTooSoonException $exception) {
                    Notification::make()->title('Товар пока нельзя поднять')
                        ->body($exception->getMessage())->warning()->send();
                    $action->halt();
                }
                Notification::make()->title('Товар поднят')->success()->send();
            });
    }

    private static function deleteProduct(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('deleteProduct')
            ->label('Удалить')->icon('heroicon-o-trash')->color('danger')->authorize('delete')
            ->requiresConfirmation()
            ->action(function (Product $record, ProductActionService $service): void {
                $service->delete($record);
                Notification::make()->title('Товар удалён')->success()->send();
            });
    }

    private static function canModerate(Product $product): bool
    {
        return app(ProductStateTransitionService::class)->canModerateManually($product);
    }
}
