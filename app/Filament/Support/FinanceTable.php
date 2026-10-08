<?php

namespace App\Filament\Support;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Общие куски таблиц раздела «Финансы»: пользователь со ссылкой, фильтры по дате и по пользователю. */
final class FinanceTable
{
    /** Имя пользователя с почтой под ним; клик ведёт в карточку пользователя. Ищет и по имени, и по почте. */
    public static function userColumn(string $label = 'Пользователь'): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('user.name')
            ->label($label)
            ->placeholder('Пользователь удалён')
            ->description(fn (Model $record): ?string => $record->user?->email)
            ->url(fn (Model $record): ?string => $record->user
                ? UserResource::getUrl('edit', ['record' => $record->user])
                : null)
            ->limit(26)
            ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                'user',
                fn (Builder $user): Builder => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ));
    }

    public static function userFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('user_id')
            ->label('Пользователь')
            ->searchable()
            ->getSearchResultsUsing(fn (string $search): array => User::query()
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orderBy('name')
                ->limit(30)
                ->get()
                ->mapWithKeys(fn (User $user): array => [$user->getKey() => $user->name.' · '.$user->email])
                ->all())
            ->getOptionLabelUsing(fn (mixed $value): ?string => User::query()->find($value)?->name);
    }

    /** Период по дате создания (или другой колонке): «с» и «по» включительно. */
    public static function periodFilter(string $column = 'created_at', string $label = 'Период'): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('period_'.$column)
            ->label($label)
            ->form([
                Forms\Components\DatePicker::make('from')->label($label.': с'),
                Forms\Components\DatePicker::make('until')->label($label.': по'),
            ])
            ->columns(2)
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($column, '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($column, '<=', $date)))
            ->indicateUsing(function (array $data) use ($label): array {
                $indicators = [];

                if ($data['from'] ?? null) {
                    $indicators[] = Indicator::make($label.' с '.Carbon::parse($data['from'])->format('d.m.Y'))->removeField('from');
                }

                if ($data['until'] ?? null) {
                    $indicators[] = Indicator::make($label.' по '.Carbon::parse($data['until'])->format('d.m.Y'))->removeField('until');
                }

                return $indicators;
            });
    }
}
