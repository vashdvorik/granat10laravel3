<?php

namespace App\Filament\Resources\ProductResource\Tables;

use App\Enums\ProductModerationStatus;
use App\Models\Product;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ProductTableLayout
{
    public static function columns(): array
    {
        return [
            Tables\Columns\ImageColumn::make('mainImage.path')
                ->label('Фото')->disk('public')->size(44)->square(),
            Tables\Columns\TextColumn::make('name')
                ->label('Название')->searchable(['name', 'alias'])->limit(35)->wrap()
                ->tooltip(fn (Product $record): string => $record->name)->sortable(),
            Tables\Columns\TextColumn::make('seller.name')
                ->label('Продавец')->searchable()->limit(24)->sortable(),
            Tables\Columns\TextColumn::make('category.name')
                ->label('Категория')->limit(24)->toggleable(isToggledHiddenByDefault: true)->sortable(),
            Tables\Columns\TextColumn::make('price')
                ->label('Цена')
                ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2, ',', ' ').' RUP')
                ->sortable(),
            Tables\Columns\TextColumn::make('lowprice')
                ->label('Скидка')
                ->formatStateUsing(fn (mixed $state): string => filled($state)
                    ? number_format((float) $state, 2, ',', ' ').' RUP'
                    : '—')
                ->sortable()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\IconColumn::make('is_active')->label('Публикация')->boolean(),
            Tables\Columns\TextColumn::make('moderation_status')
                ->label('Модерация')->badge()
                ->formatStateUsing(fn (ProductModerationStatus $state): string => $state->label())
                ->color(fn (ProductModerationStatus $state): string => $state->color())
                ->sortable(),
            Tables\Columns\IconColumn::make('has_image_moderation_problem')
                ->label('Фото')
                ->getStateUsing(fn (Product $record): bool => self::hasImageModerationProblem($record))
                ->boolean()
                ->trueIcon('heroicon-o-exclamation-triangle')
                ->falseIcon('heroicon-o-check-circle')
                ->trueColor('warning')
                ->falseColor('success')
                ->tooltip(fn (Product $record): string => self::hasImageModerationProblem($record)
                    ? 'Есть проблема по фото'
                    : 'Проблем по фото не найдено')
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\IconColumn::make('is_vip')
                ->label('VIP')->boolean()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('images_count')
                ->label('Фото')->alignCenter()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('push_at')
                ->label('Поднят')->dateTime('d.m.Y H:i')->placeholder('—')->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('seller_id')
                ->label('Продавец')->relationship('seller', 'name')->searchable()->preload(),
            Tables\Filters\SelectFilter::make('category_id')
                ->label('Категория')->relationship('category', 'name')->searchable()->preload(),
            Tables\Filters\TernaryFilter::make('is_active')->label('Активность'),
            Tables\Filters\TernaryFilter::make('is_vip')->label('VIP'),
            Tables\Filters\SelectFilter::make('moderation_status')
                ->label('Модерация')
                ->options(collect(ProductModerationStatus::cases())
                    ->mapWithKeys(fn (ProductModerationStatus $status): array => [
                        $status->value => $status->label(),
                    ])->all()),
            Tables\Filters\TernaryFilter::make('image_moderation_problem')
                ->label('Проблемы с фото')
                ->queries(
                    true: fn (Builder $query): Builder => $query->whereHas(
                        'moderationRuns',
                        fn (Builder $runs): Builder => self::imageProblemRunQuery($runs)
                    ),
                    false: fn (Builder $query): Builder => $query->whereDoesntHave(
                        'moderationRuns',
                        fn (Builder $runs): Builder => self::imageProblemRunQuery($runs)
                    ),
                ),
            Tables\Filters\TernaryFilter::make('on_sale')
                ->label('Со скидкой')
                ->queries(
                    true: fn (Builder $query): Builder => $query->whereNotNull('lowprice'),
                    false: fn (Builder $query): Builder => $query->whereNull('lowprice'),
                ),
        ];
    }

    private static function hasImageModerationProblem(Product $product): bool
    {
        return $product->moderationRuns()
            ->where('content_version', $product->moderation_version)
            ->where(fn (Builder $query): Builder => self::imageProblemConditions($query))
            ->exists();
    }

    private static function imageProblemRunQuery(Builder $query): Builder
    {
        return $query
            ->whereColumn('content_version', 'products.moderation_version')
            ->where(fn (Builder $query): Builder => self::imageProblemConditions($query));
    }

    private static function imageProblemConditions(Builder $query): Builder
    {
        return $query
            ->where('provider', 'like', 'image_%')
            ->where(function (Builder $query): void {
                $query->where('status', 'failed')
                    ->orWhere(function (Builder $query): void {
                        $query->where('status', 'completed')
                            ->where(function (Builder $query): void {
                                $query->whereNull('decision')
                                    ->orWhere('decision', '!=', 'allow');
                            });
                    });
            });
    }
}
