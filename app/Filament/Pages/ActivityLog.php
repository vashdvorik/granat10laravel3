<?php

namespace App\Filament\Pages;

use App\Models\Seller;
use App\Models\User;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Page implements HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Система';

    protected static ?string $navigationLabel = 'Журнал действий';

    protected static ?string $title = 'Журнал действий';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.activity-log';

    public static function canAccess(): bool
    {
        return Gate::allows('access-admin');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->with(['causer', 'subject'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Группа')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'auth' => 'info',
                        'products' => 'success',
                        'sellers' => 'warning',
                        'packages' => 'danger',
                        'user_balance', 'user_roles', 'users' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Действие')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Инициатор')
                    ->description(fn (Activity $record): ?string => $record->causer?->email)
                    ->placeholder('Система'),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Объект')
                    ->state(fn (Activity $record): ?string => $record->subject_type
                        ? class_basename($record->subject_type).' #'.$record->subject_id
                        : null)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('properties.ip')
                    ->label('IP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Группа')
                    ->options(fn (): array => Activity::query()
                        ->whereNotNull('log_name')
                        ->distinct()
                        ->orderBy('log_name')
                        ->pluck('log_name', 'log_name')
                        ->all()),
                Tables\Filters\Filter::make('causer')
                    ->label('Инициатор')
                    ->form([
                        Forms\Components\Select::make('value')
                            ->label('Инициатор')
                            ->options(fn (): array => User::query()
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (User $user): array => [
                                    $user->id => "{$user->name} ({$user->email})",
                                ])
                                ->all())
                            ->searchable(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['value'] ?? null),
                            fn (Builder $query): Builder => $query
                                ->where('causer_type', User::class)
                                ->where('causer_id', $data['value'])
                        ))
                    ->indicateUsing(function (array $data): ?string {
                        if (! filled($data['value'] ?? null)) {
                            return null;
                        }

                        $user = User::query()->find($data['value']);

                        return $user ? 'Инициатор: '.$user->name : null;
                    }),
                Tables\Filters\Filter::make('seller_name')
                    ->label('Магазин')
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label('Название магазина')
                            ->placeholder('Введите часть названия'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = trim((string) ($data['value'] ?? ''));

                        if ($search === '') {
                            return $query;
                        }

                        $sellers = Seller::query()
                            ->where('name', 'like', "%{$search}%")
                            ->get(['id', 'user_id']);
                        $sellerIds = $sellers->pluck('id');
                        $userIds = $sellers->pluck('user_id')->filter();

                        return $query->where(function (Builder $query) use (
                            $search,
                            $sellerIds,
                            $userIds
                        ): void {
                            $query
                                ->where('properties->seller_name', 'like', "%{$search}%")
                                ->orWhere(function (Builder $query) use ($sellerIds): void {
                                    $query
                                        ->where('subject_type', Seller::class)
                                        ->whereIn('subject_id', $sellerIds);
                                })
                                ->orWhere(function (Builder $query) use ($userIds): void {
                                    $query
                                        ->where('causer_type', User::class)
                                        ->whereIn('causer_id', $userIds);
                                });
                        });
                    })
                    ->indicateUsing(fn (array $data): ?string => filled($data['value'] ?? null)
                        ? 'Магазин: '.$data['value']
                        : null),
            ])
            ->actions([
                Tables\Actions\Action::make('viewDetails')
                    ->label('Подробнее')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Activity $record): string => "Запись #{$record->id}")
                    ->modalContent(fn (Activity $record): HtmlString => new HtmlString(
                        view('filament.pages.partials.activity-log-details', [
                            'activity' => $record,
                        ])->render()
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть'),
            ])
            ->defaultPaginationPageOption(30)
            ->paginationPageOptions([30, 50, 100])
            ->emptyStateHeading('Записей не найдено')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
