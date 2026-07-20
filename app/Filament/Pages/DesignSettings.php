<?php

namespace App\Filament\Pages;

use App\Services\DesignManager;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

class DesignSettings extends Page implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationGroup = 'Система';

    protected static ?string $navigationLabel = 'Дизайн сайта';

    protected static ?string $title = 'Дизайн сайта';

    protected static ?int $navigationSort = 92;

    protected static string $view = 'filament.pages.design-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Gate::allows('access-admin');
    }

    public function mount(DesignManager $design): void
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
                    ->label('Активный дизайн')
                    ->options(config('design.themes'))
                    ->descriptions([
                        'legacy' => 'Текущие Blade-шаблоны и ассеты сайта.',
                        'new' => 'Новые шаблоны с автоматическим возвратом к старому дизайну для ещё не перенесённых страниц.',
                    ])
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(DesignManager $design): void
    {
        $data = $this->form->getState();
        $design->setActive($data['theme']);

        Notification::make()
            ->title('Дизайн сайта изменён')
            ->body('Выбранный дизайн теперь видят все посетители.')
            ->success()
            ->send();
    }
}
