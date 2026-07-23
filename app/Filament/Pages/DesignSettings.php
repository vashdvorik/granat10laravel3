<?php

namespace App\Filament\Pages;

use App\Rules\CatalogBannerLink;
use App\Services\CatalogBannerManager;
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

    public function mount(DesignManager $design, CatalogBannerManager $catalogBanner): void
    {
        $this->form->fill([
            'theme' => $design->current(),
            'catalog_banners' => $catalogBanner->all(),
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
                Forms\Components\Repeater::make('catalog_banners')
                    ->label('Баннеры каталога и страницы 404')
                    ->helperText('При каждой загрузке страницы посетителю показывается случайный баннер из этого списка. Рекомендуемый размер каждого изображения: 1398 × 316 px.')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Изображение баннера')
                            ->helperText('Для корректного отображения используйте изображение размером 1398 × 316 px.')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->disk(config('catalog_banner.disk'))
                            ->directory(config('catalog_banner.directory'))
                            ->visibility('public')
                            ->imagePreviewHeight('180')
                            ->openable()
                            ->downloadable()
                            ->required(),
                        Forms\Components\TextInput::make('link')
                            ->label('Ссылка баннера')
                            ->placeholder('/sale или https://example.com/page')
                            ->helperText('Можно указать внутренний путь или полный URL. Если оставить поле пустым, баннер не будет выполнять переход.')
                            ->maxLength(2048)
                            ->rules(['nullable', new CatalogBannerLink]),
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->defaultItems(0)
                    ->addActionLabel('Добавить баннер')
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(static fn (array $state): string => filled($state['image'] ?? null)
                        ? 'Настроенный баннер'
                        : 'Новый баннер'),
            ])
            ->statePath('data');
    }

    public function save(DesignManager $design, CatalogBannerManager $catalogBanner): void
    {
        $data = $this->form->getState();
        $design->setActive($data['theme']);
        $catalogBanner->setBanners($data['catalog_banners'] ?? []);

        Notification::make()
            ->title('Дизайн сайта изменён')
            ->body('Выбранный дизайн теперь видят все посетители.')
            ->success()
            ->send();
    }
}
