<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Pages\CategoryTree;
use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('tree')
                ->label('Дерево категорий')
                ->icon('heroicon-o-list-bullet')
                ->url(CategoryTree::getUrl()),
            Actions\CreateAction::make(),
        ];
    }
}
