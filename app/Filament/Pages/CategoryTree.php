<?php

namespace App\Filament\Pages;

use App\Models\Category;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class CategoryTree extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Управление';

    protected static ?string $navigationLabel = 'Дерево категорий';

    protected static ?string $title = 'Дерево категорий';

    protected static ?int $navigationSort = 41;

    protected static string $view = 'filament.pages.category-tree';

    protected function getViewData(): array
    {
        return [
            'tree' => Category::query()
                ->withCount('products')
                ->defaultOrder()
                ->get()
                ->toTree(),
        ];
    }

    public static function canAccess(): bool
    {
        return Gate::allows('viewAny', Category::class);
    }
}
