<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Services\CategoryTreeService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(CategoryTreeService::class)->update(auth()->user(), $record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('tree')
                ->label('Показать в дереве')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(fn (): string => CategoryResource::getUrl('index', ['focus' => $this->getRecord()->getKey()])),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}
