<?php

namespace App\Filament\Pages;

use App\Data\ModerationSettingsSnapshot;
use App\Models\Category;
use App\Services\ModerationConnectionTester;
use App\Services\ModerationSettingsFormMapper;
use App\Services\ModerationSettingsSnapshotFactory;
use App\Services\ModerationSettingsWriter;
use App\Services\ModerationStatusSummary;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class ModerationSettings extends Page implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

    private const ADVANCED_SECTION_ID = 'moderation-advanced';

    /** Поля блока «Дополнительные настройки»: при ошибке в них блок раскрывается сам. */
    private const ADVANCED_FIELDS = [
        'queue_connection', 'queue', 'timeout', 'max_attempts',
        'max_output_tokens', 'retry_times', 'retry_sleep_ms',
        'requests_per_minute', 'rate_limit_safety_seconds', 'hourly_request_limit', 'daily_request_limit',
        'image_queue', 'image_timeout', 'image_max_attempts',
    ];

    /** Модель OpenRouter: «автор/название», например openai/gpt-4o-mini или meta-llama/llama-3.1-8b-instruct:free. */
    private const MODEL_PATTERN = '/^[A-Za-z0-9_.\-]+\/[A-Za-z0-9_.\-:+]+$/';

    private const MODEL_MESSAGE = 'Укажите модель в виде «автор/название», например openai/gpt-4o-mini.';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Система';

    protected static ?string $navigationLabel = 'Настройки модерации';

    protected static ?string $title = 'Настройки ИИ-модерации';

    protected static ?int $navigationSort = 91;

    protected static string $view = 'filament.pages.moderation-settings';

    public ?array $data = [];

    public bool $apiKeyConfigured = false;

    public array $budgetUsage = [];

    public array $operationsStatus = [];

    public array $statusSummary = [];

    /** Filament вызывает validate() дважды за одно сохранение: сообщаем об ошибке один раз за запрос. */
    private bool $validationErrorReported = false;

    public static function canAccess(): bool
    {
        return Gate::allows('access-admin');
    }

    public function mount(ModerationSettingsSnapshotFactory $factory): void
    {
        $this->applySnapshot($factory->make());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->strategySection(),
                $this->connectionSection(),
                $this->imageSection(),
                $this->advancedSection(),
            ])
            ->statePath('data');
    }

    // ------------------------------------------------------------------ главное решение

    private function strategySection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Как проверять новые товары')
            ->description('Главная настройка. Всё остальное на странице можно не трогать: значения уже подобраны.')
            ->schema([
                Forms\Components\Radio::make('strategy')
                    ->hiddenLabel()
                    ->required()
                    ->live()
                    ->options([
                        ModerationSettingsFormMapper::STRATEGY_MANUAL => 'Только вручную',
                        ModerationSettingsFormMapper::STRATEGY_ASSIST => 'ИИ подсказывает, решает человек',
                        ModerationSettingsFormMapper::STRATEGY_AUTO => 'ИИ публикует безопасные товары сам',
                    ])
                    ->descriptions([
                        ModerationSettingsFormMapper::STRATEGY_MANUAL => 'ИИ не используется. Каждый новый товар проверяет модератор.',
                        ModerationSettingsFormMapper::STRATEGY_ASSIST => 'ИИ проверяет товар и оставляет свой вывод: модератор видит его в карточке товара. Публикует или возвращает продавцу всегда человек. Хороший вариант, чтобы присмотреться к работе ИИ.',
                        ModerationSettingsFormMapper::STRATEGY_AUTO => 'Если риск низкий, товар выходит на сайт без человека. Если ИИ нашёл, что исправить, продавец получает подсказку; всё сомнительное уходит модератору.',
                    ]),

                Forms\Components\Placeholder::make('auto_notice')
                    ->hiddenLabel()
                    ->visible(fn (Forms\Get $get): bool => $get('strategy') === ModerationSettingsFormMapper::STRATEGY_AUTO)
                    ->content(new HtmlString('<div style="padding:.75rem 1rem;border-radius:.5rem;border:1px solid rgba(217,119,6,.5);background:rgba(217,119,6,.08);font-size:.9rem;line-height:1.5">Товары будут выходить на сайт без проверки человеком. Включайте этот вариант, когда в режиме «ИИ подсказывает» вы убедились, что ИИ почти не ошибается.</div>')),

                Forms\Components\Fieldset::make('Когда ИИ может публиковать сам')
                    ->visible(fn (Forms\Get $get): bool => $get('strategy') === ModerationSettingsFormMapper::STRATEGY_AUTO)
                    ->dehydratedWhenHidden()
                    ->columns(1)
                    ->schema([
                        Forms\Components\ToggleButtons::make('strictness')
                            ->label('Насколько строго')
                            ->inline()
                            ->live()
                            ->dehydratedWhenHidden()
                            ->options([
                                'strict' => 'Строго',
                                'normal' => 'Обычно',
                                'soft' => 'Мягко',
                                ModerationSettingsFormMapper::STRICTNESS_CUSTOM => 'Своё значение',
                            ])
                            ->helperText('Чем строже, тем больше товаров ИИ передаёт модератору. «Обычно» — разумный выбор для начала: ИИ публикует товар сам, только если оценил риск не выше 10%.'),
                        Forms\Components\TextInput::make('max_risk')
                            ->label('Максимальный риск для автопубликации, %')
                            ->numeric()->minValue(0)->maxValue(100)
                            ->visible(fn (Forms\Get $get): bool => $get('strictness') === ModerationSettingsFormMapper::STRICTNESS_CUSTOM)
                            ->required(fn (Forms\Get $get): bool => $get('strictness') === ModerationSettingsFormMapper::STRICTNESS_CUSTOM)
                            ->dehydratedWhenHidden()
                            ->helperText('От 0 до 100. Чем меньше число, тем строже: 5 — строго, 10 — обычно, 25 — мягко.'),
                        Forms\Components\Select::make('excluded_categories')
                            ->label('Категории, где ИИ не публикует сам')
                            ->multiple()
                            ->searchable()
                            ->options(fn (): array => $this->categoryOptions())
                            ->dehydratedWhenHidden()
                            ->helperText('Товары из этих категорий всегда идут на проверку модератору. Подкатегории выбирайте отдельно. Если ничего не выбрано, ограничений нет.'),
                    ]),
            ]);
    }

    // ------------------------------------------------------------------ подключение к OpenRouter

    private function connectionSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Подключение к OpenRouter')
            ->description('Через OpenRouter работают и проверка текста, и проверка фото: один ключ на всё.')
            ->schema([
                Forms\Components\TextInput::make('api_key')
                    ->label('Ключ доступа (API-ключ)')
                    // Chrome игнорирует autocomplete="off" у полей пароля и подставляет сюда сохранённый пароль от сайта,
                    // а прежний текстовый ввод получает логин. new-password и метки менеджеров паролей это отключают.
                    ->password()->revealable()->autocomplete('new-password')
                    ->extraInputAttributes(['data-lpignore' => 'true', 'data-1p-ignore' => 'true', 'data-bwignore' => 'true'])
                    ->placeholder(fn (): string => $this->apiKeyConfigured ? 'Ключ сохранён — вводить заново не нужно' : 'Ключ ещё не задан')
                    ->helperText('Создаётся на openrouter.ai/keys. Хранится в зашифрованном виде и не показывается. Оставьте поле пустым, чтобы не менять сохранённый ключ.'),
                Forms\Components\TextInput::make('text_model')
                    ->label('Модель для текста')
                    ->required()
                    ->regex(self::MODEL_PATTERN)
                    ->validationMessages(['regex' => self::MODEL_MESSAGE])
                    ->datalist(['openai/gpt-4o-mini', 'google/gemini-2.5-flash', 'google/gemini-2.0-flash-001', 'deepseek/deepseek-chat', 'anthropic/claude-3.5-haiku'])
                    ->helperText('Название модели с openrouter.ai/models в виде «автор/название», например openai/gpt-4o-mini. Подойдёт недорогая быстрая модель.'),
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('testTextConnection')
                        ->label('Проверить подключение')
                        ->icon('heroicon-m-signal')
                        ->color('gray')
                        ->action(fn () => $this->testTextConnection(app(ModerationConnectionTester::class))),
                ])->columnSpanFull(),
                Forms\Components\Placeholder::make('text_test_hint')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->content(new HtmlString('<span style="font-size:.85rem;opacity:.75">Проверка использует сохранённые настройки: если вы что-то поменяли, сначала нажмите «Сохранить».</span>')),
            ])
            ->columns(2);
    }

    // ------------------------------------------------------------------ фото

    private function imageSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Фото товаров')
            ->description('Отдельная проверка фотографий. Работает независимо от проверки текста и использует тот же ключ.')
            ->schema([
                Forms\Components\Toggle::make('image_moderation_enabled')
                    ->label('Проверять фотографии с помощью ИИ')
                    ->helperText('Если включено, товар не опубликуется, пока фото не пройдут проверку. Без проверки фото ИИ не сможет публиковать товары с фотографиями сам: они уйдут модератору.')
                    ->live()
                    ->columnSpanFull(),
                // Пока проверка фото выключена, поле модели скрыто, но его значение сохраняется
                Forms\Components\Group::make([
                    Forms\Components\TextInput::make('image_model')
                        ->label('Модель для фото')
                        ->required()
                        ->regex(self::MODEL_PATTERN)
                        ->validationMessages(['regex' => self::MODEL_MESSAGE])
                        ->datalist(['openai/gpt-4o-mini', 'google/gemini-2.5-flash', 'google/gemini-2.0-flash-001', 'qwen/qwen2.5-vl-72b-instruct'])
                        ->helperText('Нужна модель, которая умеет читать изображения. Проверьте кнопкой ниже.'),
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('testImageConnection')
                            ->label('Проверить подключение')
                            ->icon('heroicon-m-signal')
                            ->color('gray')
                            ->action(fn () => $this->testImageConnection(app(ModerationConnectionTester::class))),
                    ]),
                    Forms\Components\Placeholder::make('image_test_hint')
                        ->hiddenLabel()
                        ->content(new HtmlString('<span style="font-size:.85rem;opacity:.75">Проверка использует сохранённые настройки: если вы что-то поменяли, сначала нажмите «Сохранить».</span>')),
                ])
                    ->columnSpanFull()
                    ->visible(fn (Forms\Get $get): bool => (bool) $get('image_moderation_enabled'))
                    ->dehydratedWhenHidden(),
            ]);
    }

    // ------------------------------------------------------------------ для специалиста

    private function advancedSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Дополнительные настройки')
            ->id(self::ADVANCED_SECTION_ID)
            ->description('Для специалиста по серверу. Значения подобраны заранее и работают без правок: менять их нужно редко.')
            ->collapsible()
            ->collapsed()
            ->schema([
                Forms\Components\Fieldset::make('Очередь проверок')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('queue_connection')
                            ->label('Где выполняются проверки')
                            ->options(fn (): array => $this->queueConnectionOptions())
                            ->required()
                            ->helperText('На рабочем сервере нужен вариант Database и запущенный обработчик очереди.'),
                        Forms\Components\TextInput::make('queue')
                            ->label('Название очереди')->required(),
                        Forms\Components\TextInput::make('timeout')
                            ->label('Сколько ждать ответ ИИ, секунд')
                            ->numeric()->minValue(5)->maxValue(300)->required(),
                        Forms\Components\TextInput::make('max_attempts')
                            ->label('Сколько раз пробовать')
                            ->numeric()->minValue(1)->maxValue(10)->required(),
                    ]),

                Forms\Components\Fieldset::make('Лимиты запросов к ИИ')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('hourly_request_limit')
                            ->label('Максимум запросов в час')
                            ->helperText('Когда лимит исчерпан, новые товары идут на ручную проверку без обращения к ИИ.')
                            ->numeric()->minValue(1)->maxValue(100000)->required(),
                        Forms\Components\TextInput::make('daily_request_limit')
                            ->label('Максимум запросов в сутки')
                            ->helperText('Защищает от неожиданного расхода денег на OpenRouter.')
                            ->numeric()->minValue(1)->maxValue(1000000)->required(),
                        Forms\Components\TextInput::make('requests_per_minute')
                            ->label('Максимум запросов в минуту')
                            ->numeric()->minValue(1)->maxValue(10000)->required(),
                        Forms\Components\TextInput::make('rate_limit_safety_seconds')
                            ->label('Запас между запросами, секунд')
                            ->numeric()->minValue(0)->maxValue(30)->required(),
                    ]),

                Forms\Components\Fieldset::make('Повторы и размер ответа')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('max_output_tokens')
                            ->label('Максимальная длина ответа ИИ (токенов)')
                            ->helperText('Модели с «рассуждениями» тратят часть этого лимита на размышления: если ответы обрываются, увеличьте число.')
                            ->numeric()->minValue(256)->maxValue(32768)->required(),
                        Forms\Components\TextInput::make('retry_times')
                            ->label('Повторов при сбое связи')
                            ->numeric()->minValue(0)->maxValue(5)->required(),
                        Forms\Components\TextInput::make('retry_sleep_ms')
                            ->label('Пауза между повторами, миллисекунд')
                            ->numeric()->minValue(0)->maxValue(10000)->required(),
                    ]),

                Forms\Components\Fieldset::make('Проверка фото: очередь')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('image_queue')
                            ->label('Название очереди для фото')
                            ->required()
                            ->helperText('Обычно moderation.'),
                        Forms\Components\TextInput::make('image_timeout')
                            ->label('Сколько ждать ответ по фото, секунд')
                            ->numeric()->minValue(5)->maxValue(300)->required(),
                        Forms\Components\TextInput::make('image_max_attempts')
                            ->label('Сколько раз пробовать проверить фото')
                            ->numeric()->minValue(1)->maxValue(10)->required(),
                    ]),
            ]);
    }
    // ------------------------------------------------------------------ данные для полей

    /** @return array<string, string> */
    private function queueConnectionOptions(): array
    {
        $options = ['database' => 'Database — рекомендуется'];
        $current = (string) ($this->data['queue_connection'] ?? 'database');

        if ($current !== '' && $current !== 'database') {
            $options[$current] = $current === 'sync'
                ? 'Sync — только для отладки на своём компьютере'
                : $current;
        }

        return $options;
    }

    /**
     * Все категории с родителем в названии («Электроника → Телефоны»), ключ — алиас. Сохранённые алиасы,
     * которых в базе уже нет, остаются в списке, чтобы их можно было увидеть и снять.
     *
     * @return array<string, string>
     */
    private function categoryOptions(): array
    {
        $categories = Category::query()->get(['id', 'name', 'alias', 'parent_id'])->keyBy('id');

        $options = $categories
            ->filter(fn (Category $category): bool => filled($category->alias))
            ->mapWithKeys(function (Category $category) use ($categories): array {
                $path = [$category->name];
                $parent = $categories->get($category->parent_id);
                $guard = 0;

                while ($parent !== null && $guard++ < 10) {
                    array_unshift($path, $parent->name);
                    $parent = $categories->get($parent->parent_id);
                }

                return [$category->alias => implode(' → ', $path)];
            })
            ->sort()
            ->all();

        foreach ((array) ($this->data['excluded_categories'] ?? []) as $alias) {
            $options[$alias] ??= (string) $alias;
        }

        return $options;
    }

    // ------------------------------------------------------------------ сохранение

    public function save(
        ModerationSettingsWriter $writer,
        ModerationSettingsSnapshotFactory $factory,
        ModerationSettingsFormMapper $mapper,
    ): void {
        $state = $this->form->getState();
        $current = $factory->make()->data;
        $data = $mapper->toStorage($state, $current);

        try {
            $writer->save($data);
        } catch (ValidationException $exception) {
            $this->onValidationError($exception);

            throw $exception;
        }

        $this->applySnapshot($factory->make());

        Notification::make()
            ->title('Настройки сохранены')
            ->body('Новые параметры применятся к следующим заданиям модерации.')
            ->success()
            ->send();

        activity('moderation_settings')
            ->causedBy(auth()->user())
            ->withProperties([
                'text_model' => $data['text_model'],
                'image_model' => $data['image_model'],
                'mode' => $data['mode'],
                'ai_enabled' => (bool) $data['ai_enabled'],
                'image_enabled' => (bool) $data['image_moderation_enabled'],
            ])
            ->log('Изменены настройки AI-модерации');
    }

    protected function onValidationError(ValidationException $exception): void
    {
        if ($this->validationErrorReported) {
            return;
        }

        $this->validationErrorReported = true;

        $fields = collect(array_keys($exception->errors()))
            ->map(fn (string $key): string => preg_replace('/^data\./', '', $key));

        if ($fields->intersect(self::ADVANCED_FIELDS)->isNotEmpty()) {
            $this->dispatch('open-section', id: self::ADVANCED_SECTION_ID);
        }

        Notification::make()
            ->title('Настройки не сохранены')
            ->body('Исправьте отмеченные поля'.($fields->intersect(self::ADVANCED_FIELDS)->isNotEmpty() ? ' (часть из них — в блоке «Дополнительные настройки»)' : '').'.')
            ->danger()
            ->send();
    }

    // ------------------------------------------------------------------ состояние страницы

    private function applySnapshot(ModerationSettingsSnapshot $snapshot): void
    {
        $this->apiKeyConfigured = $snapshot->apiKeyConfigured;
        $this->budgetUsage = $snapshot->budgetUsage;
        $this->operationsStatus = $snapshot->operationsStatus;
        $this->statusSummary = app(ModerationStatusSummary::class)->make($snapshot, app()->isProduction());

        $this->data = app(ModerationSettingsFormMapper::class)->toForm($snapshot->data);
        // Сохранённый ключ в форму не попадает: поле пустое, пока администратор не введёт новый ключ.
        $this->data['api_key'] = null;

        $this->form->fill($this->data);
    }

    public function testTextConnection(ModerationConnectionTester $tester): void
    {
        $this->sendConnectionTestResult('Текстовая модерация', $tester->text());
    }

    public function testImageConnection(ModerationConnectionTester $tester): void
    {
        $this->sendConnectionTestResult('Модерация изображений', $tester->images());
    }

    private function sendConnectionTestResult(string $label, array $result): void
    {
        $notification = Notification::make()
            ->title($result['ok'] ? "{$label}: подключение успешно" : "{$label}: подключение не удалось")
            ->body((string) $result['message']);

        if ($result['ok']) {
            $notification->success();
        } else {
            $notification->danger();
        }

        $notification->send();
    }
}
