<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Forms\ProductForm;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\ModerationActionsRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\ModerationRunsRelationManager;
use App\Filament\Resources\ProductResource\Tables\ProductTableActions;
use App\Filament\Resources\ProductResource\Tables\ProductTableLayout;
use App\Enums\ProductModerationStatus;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Управление';

    protected static ?string $navigationLabel = 'Товары';

    protected static ?string $modelLabel = 'товар';

    protected static ?string $pluralModelLabel = 'товары';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return ProductForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(ProductTableLayout::columns())
            ->filters(ProductTableLayout::filters())
            ->actions(ProductTableActions::make())
            ->bulkActions([]);
    }

    /** Товары, по которым ждут решения администратора: чем дольше очередь, тем дольше продавцы видят «Ждёт проверки». */
    public static function getNavigationBadge(): ?string
    {
        $waiting = Product::query()
            ->whereIn('moderation_status', [
                ProductModerationStatus::Pending->value,
                ProductModerationStatus::NeedsReview->value,
                ProductModerationStatus::Failed->value,
            ])
            ->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Товары, которые ждут решения администратора (на проверке или переданы на ручную проверку).';
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
            ModerationActionsRelationManager::class,
            ModerationRunsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['seller', 'category', 'mainImage'])
            ->withCount('images');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
