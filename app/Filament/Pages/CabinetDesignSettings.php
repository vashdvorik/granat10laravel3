<?php

namespace App\Filament\Pages;

use App\Services\CabinetDesignManager;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class CabinetDesignSettings extends Page implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'Система';

    protected static ?string $navigationLabel = 'Дизайн кабинета';

    protected static ?string $title = 'Дизайн кабинета';

    protected static ?int $navigationSort = 93;

    protected static string $view = 'filament.pages.cabinet-design-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Gate::allows('access-admin');
    }

    public function mount(CabinetDesignManager $design): void
    {
        $this->form->fill([
            'theme' => $design->current(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Radio::make('theme')
                    ->label('Активный дизайн кабинета')
                    ->options(config('cabinet_design.themes'))
                    ->descriptions([
                        'legacy' => 'Текущие шаблоны кабинета продавца.',
                        'new' => 'Новые шаблоны кабинета продавца с отдельной системой оформления.',
                    ])
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(CabinetDesignManager $design): void
    {
        $data = $this->form->getState();
        $design->setActive($data['theme']);

        Notification::make()
            ->title('Дизайн кабинета изменён')
            ->body('Выбранный дизайн кабинета теперь видят все продавцы.')
            ->success()
            ->send();
    }
}
