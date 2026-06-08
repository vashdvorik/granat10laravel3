<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use App\Services\BalanceAdjustmentService;
use App\Services\UserRoleService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Управление';

    protected static ?string $navigationLabel = 'Пользователи';

    protected static ?string $modelLabel = 'пользователь';

    protected static ?string $pluralModelLabel = 'пользователи';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->rule('not_regex:/[\r\n]/')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->required()
                    ->confirmed()
                    ->minLength(8)
                    ->visibleOn('create'),
                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Подтверждение пароля')
                    ->password()
                    ->required()
                    ->dehydrated(false)
                    ->visibleOn('create'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Баланс')
                    ->formatStateUsing(fn (mixed $state): string => number_format((float) $state, 2, ',', ' ').' RUP')
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role.display_name')
                    ->label('Основная роль')
                    ->placeholder('Без роли')
                    ->badge(),
                Tables\Columns\IconColumn::make('seller')
                    ->label('Магазин')
                    ->state(fn (User $record): bool => $record->seller !== null)
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Регистрация')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->options(fn (): array => Role::query()
                        ->orderBy('display_name')
                        ->pluck('display_name', 'id')
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, mixed $roleId): Builder => $query->where(function (Builder $query) use ($roleId): void {
                                $query
                                    ->where('role_id', $roleId)
                                    ->orWhereHas('roles', fn (Builder $query): Builder => $query->whereKey($roleId));
                            }),
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resetPassword')
                    ->label('Сбросить пароль')
                    ->icon('heroicon-o-key')
                    ->requiresConfirmation()
                    ->modalDescription('Пользователю будет отправлена ссылка для сброса пароля.')
                    ->action(function (User $record): void {
                        $status = Password::sendResetLink(['email' => $record->email]);

                        if ($status === Password::RESET_LINK_SENT) {
                            Notification::make()
                                ->title('Ссылка для сброса пароля отправлена')
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Не удалось отправить ссылку')
                            ->body(__($status))
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\Action::make('assignRole')
                    ->label('Назначить роль')
                    ->icon('heroicon-o-user-plus')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('role_id')
                            ->label('Основная роль')
                            ->options(fn (): array => Role::query()
                                ->orderBy('display_name')
                                ->pluck('display_name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (User $record, array $data, UserRoleService $service): void {
                        $role = Role::query()->findOrFail($data['role_id']);
                        $service->assignPrimaryRole(auth()->user(), $record, $role);

                        Notification::make()
                            ->title('Роль назначена')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('removeRole')
                    ->label('Снять роль')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('role_id')
                            ->label('Роль')
                            ->options(fn (User $record): array => $record->rolesAll()
                                ->mapWithKeys(fn (Role $role): array => [
                                    $role->getKey() => $role->display_name,
                                ])
                                ->all())
                            ->required(),
                    ])
                    ->action(function (User $record, array $data, UserRoleService $service): void {
                        $role = Role::query()->findOrFail($data['role_id']);
                        $service->removeRole(auth()->user(), $record, $role);

                        Notification::make()
                            ->title('Роль снята')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('adjustBalance')
                    ->label('Изменить баланс')
                    ->icon('heroicon-o-banknotes')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Сумма корректировки')
                            ->helperText('Используйте положительное число для пополнения и отрицательное для списания.')
                            ->numeric()
                            ->step(0.01)
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Причина')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (User $record, array $data, BalanceAdjustmentService $service): void {
                        $service->adjust(auth()->user(), $record, $data['amount'], $data['reason']);

                        Notification::make()
                            ->title('Баланс изменён')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['role', 'roles', 'seller']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
