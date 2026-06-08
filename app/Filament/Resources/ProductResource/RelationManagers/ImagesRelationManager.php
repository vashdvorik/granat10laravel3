<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Exceptions\ProductImageDeletionException;
use App\Models\Image;
use App\Models\Product;
use App\Services\ProductActionService;
use App\Services\ProductImageService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Изображения';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('path')
            ->defaultSort('order')
            ->reorderable('order')
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Изображение')
                    ->disk('public')
                    ->height(100)
                    ->width(100),
                Tables\Columns\IconColumn::make('is_main')
                    ->label('Главное')
                    ->boolean(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Порядок')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Загружено')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('uploadImages')
                    ->label('Загрузить изображения')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('images')
                            ->label('Изображения')
                            ->multiple()
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->maxFiles(5)
                            ->storeFiles(false)
                            ->required(),
                    ])
                    ->action(function (array $data, ProductImageService $service): void {
                        /** @var Product $product */
                        $product = $this->getOwnerRecord();
                        Gate::authorize('manageImages', $product);

                        $service->storeUploadedImages(
                            $product,
                            $data['images'],
                            ! $product->images()->where('is_main', true)->exists(),
                        );

                        Notification::make()
                            ->title('Изображения загружены')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('setMain')
                    ->label('Сделать главным')
                    ->icon('heroicon-o-star')
                    ->visible(fn (Image $record): bool => ! (bool) $record->is_main)
                    ->requiresConfirmation()
                    ->action(function (Image $record, ProductActionService $service): void {
                        Gate::authorize('manageImages', $record->product);
                        $service->setMainImage($record);

                        Notification::make()
                            ->title('Главное изображение изменено')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('deleteImage')
                    ->label('Удалить')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (
                        Image $record,
                        ProductActionService $service,
                        Tables\Actions\Action $action
                    ): void {
                        Gate::authorize('manageImages', $record->product);

                        try {
                            $service->deleteImage($record);
                        } catch (ProductImageDeletionException $exception) {
                            Notification::make()
                                ->title('Изображение не удалено')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            $action->halt();
                        }

                        Notification::make()
                            ->title('Изображение удалено')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    protected function canReorder(): bool
    {
        return Gate::allows('manageImages', $this->getOwnerRecord());
    }
}
