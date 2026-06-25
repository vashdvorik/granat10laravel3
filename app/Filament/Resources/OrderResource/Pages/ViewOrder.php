<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Services\OrderManagementService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('changeStatus')
                ->label('Изменить статус')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => ! $this->getRecord()->isFinal())
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Новый статус')
                        ->options(fn (): array => collect($this->getRecord()->status->allowedTransitions())
                            ->mapWithKeys(fn (OrderStatus $status): array => [$status->value => $status->label()])
                            ->all())
                        ->required(),
                    Forms\Components\Textarea::make('comment')
                        ->label('Комментарий')
                        ->maxLength(1000),
                ])
                ->action(function (array $data, OrderManagementService $service): void {
                    $service->transition(
                        $this->getRecord(),
                        OrderStatus::from($data['status']),
                        auth()->user(),
                        $data['comment'] ?? null,
                    );

                    Notification::make()
                        ->title('Статус заказа обновлён')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'status_changed_at']);
                }),
        ];
    }
}
