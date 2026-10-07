<?php

namespace App\Filament\Pages;

use App\Services\VipPricing;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class VipSettings extends Page implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Система';

    protected static ?string $navigationLabel = 'Настройки VIP';

    protected static ?string $title = 'Настройки VIP';

    protected static ?int $navigationSort = 94;

    protected static string $view = 'filament.pages.vip-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Gate::allows('access-admin');
    }

    public function mount(VipPricing $pricing): void
    {
        $this->form->fill([
            'price_per_day' => $pricing->pricePerDay(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('price_per_day')
                    ->label('Стоимость одного дня VIP, руб')
                    ->helperText('Цена VIP на 1 день, 7 дней, месяц (30 дней) и год (365 дней) считается как эта сумма, умноженная на количество дней.')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(999999)
                    ->step(0.01)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(VipPricing $pricing): void
    {
        abort_unless(Gate::allows('access-admin'), 403);

        $data = $this->form->getState();
        $pricing->setPricePerDay($data['price_per_day']);

        Notification::make()
            ->title('Стоимость VIP сохранена')
            ->body('Новая цена действует для всех следующих покупок VIP.')
            ->success()
            ->send();
    }

    /**
     * @return array<int, array{days: int, label: string, price: string}>
     */
    public function getPreview(): array
    {
        return app(VipPricing::class)->options();
    }
}
