<?php

namespace App\Filament\Pages;

use App\Data\ModerationSettingsSnapshot;
use App\Services\AiModerationRequestBudget;
use App\Services\AiProviderUrlValidator;
use App\Services\ModerationConnectionTester;
use App\Services\ModerationOperationsStatus;
use App\Services\ModerationSettingsSnapshotFactory;
use App\Services\ModerationSettingsWriter;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

class ModerationSettings extends Page implements HasForms
{
    use Forms\Concerns\InteractsWithForms;

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
                Forms\Components\Section::make('Краткая инструкция')
                    ->description('Коротко о том, как пользоваться параметрами ниже.')
                    ->schema([
                        Forms\Components\Placeholder::make('moderation_help')
                            ->content(new HtmlString('<div style="display:grid;gap:.5rem;font-size:.92rem;line-height:1.55;color:#4b5563"><div><strong>Включить ИИ-модерацию</strong> — общий выключатель. Если он выключен, новые товары идут на ручную проверку администратора.</div><div><strong>Режим работы</strong> — <strong>shadow</strong> только пишет решение ИИ, <strong>auto</strong> автоматически публикует безопасные товары.</div><div><strong>Максимальный риск для автопубликации</strong> — порог от 0 до 100. Чем ниже число, тем строже фильтр.</div><div><strong>Категории без автопубликации</strong> — укажите алиасы через запятую. Для этих категорий автопубликация не включается.</div><div><strong>Формат API</strong> — схема подключения для модерации изображений. После смены провайдера проверьте модель, URL и заголовок авторизации.</div><div><strong>Префикс ключа</strong> — обычно <strong>Bearer</strong>. Для заголовка <strong>x-goog-api-key</strong> поле нужно оставить пустым.</div><div><strong>Очередь, таймаут, попытки</strong> — параметры работы воркера. Если очередь не запущена, проверки будут зависать или не выполняться.</div></div>'))
                    ])
                    ->compact(),

                Forms\Components\Section::make('Основной режим')
                    ->schema([
                        Forms\Components\Toggle::make('ai_enabled')
                            ->label('Включить ИИ-модерацию'),
                        Forms\Components\Select::make('mode')
                            ->label('Режим работы')
                            ->options([
                                'shadow' => 'Теневой — ИИ только записывает решение',
                                'auto' => 'Автоматический — безопасные товары публикуются',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('max_risk')
                            ->label('Максимальный риск для автопубликации, %')
                            ->numeric()->minValue(0)->maxValue(100)->required(),
                        Forms\Components\TextInput::make('excluded_categories')
                            ->label('Категории без автопубликации')
                            ->helperText('Алиасы через запятую. Пустое поле разрешает автопубликацию во всех категориях при успешной проверке.'),
                    ])->columns(2),

                Forms\Components\Section::make('Очередь')
                    ->description('На рабочем сервере queue worker должен работать постоянно.')
                    ->schema([
                        Forms\Components\Select::make('queue_connection')
                            ->label('Подключение очереди')
                            ->options([
                                'database' => 'Database — рекомендуется',
                                'sync' => 'Sync — только для локальной отладки',
                            ])->required(),
                        Forms\Components\TextInput::make('queue')
                            ->label('Название очереди')->required(),
                        Forms\Components\TextInput::make('timeout')
                            ->label('Таймаут, секунд')->numeric()->minValue(5)->maxValue(300)->required(),
                        Forms\Components\TextInput::make('max_attempts')
                            ->label('Количество попыток')->numeric()->minValue(1)->maxValue(10)->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Подключение нейросети')
                    ->description($this->apiKeyConfigured
                        ? 'API-ключ настроен. Оставьте поле ключа пустым, чтобы сохранить текущее значение.'
                        : 'Укажите параметры API выбранного сервиса.')
                    ->schema([
                        Forms\Components\Select::make('provider')
                            ->label('Протокол API')
                            ->options([
                                'google' => 'Google Gemini / Gemma',
                                'deepseek' => 'DeepSeek',
                                'openai_compatible' => 'OpenAI-compatible API',
                            ])
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                                if ($state === 'deepseek') {
                                    $set('model', 'deepseek-chat');
                                    $set('base_url', 'https://api.deepseek.com');
                                    $set('chat_completions_path', '/chat/completions');
                                }

                                if ($state === 'google') {
                                    $set('model', 'gemini-3.5-flash');
                                    $set('base_url', 'https://generativelanguage.googleapis.com/v1beta');
                                    $set('chat_completions_path', '/chat/completions');
                                }
                            })
                            ->required()
                            ->helperText('Выбирается формат запросов API, а не конкретная компания.'),
                        Forms\Components\TextInput::make('api_key')
                            ->label('Новый API-ключ')
                            ->password()->revealable()->autocomplete(false)
                            ->helperText('Для локального API без авторизации поле можно оставить пустым.'),
                        Forms\Components\TextInput::make('model')
                            ->label('Идентификатор модели')
                            ->required()
                            ->helperText('Например: gemini-2.5-flash, gpt-4.1-mini или имя модели вашего сервиса.'),
                        Forms\Components\TextInput::make('base_url')
                            ->label('Базовый URL API')
                            ->url()->required()
                            ->rule(function (AiProviderUrlValidator $validator): \Closure {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($validator): void {
                                    try {
                                        $validator->validate((string) $value);
                                    } catch (\InvalidArgumentException $exception) {
                                        $fail($exception->getMessage());
                                    }
                                };
                            })
                            ->helperText('Google: https://generativelanguage.googleapis.com/v1beta. OpenAI-compatible: адрес, заканчивающийся обычно на /v1.'),
                        Forms\Components\TextInput::make('chat_completions_path')
                            ->label('Путь Chat Completions')
                            ->visible(fn (Forms\Get $get): bool => $get('provider') === 'openai_compatible')
                            ->required(fn (Forms\Get $get): bool => $get('provider') === 'openai_compatible')
                            ->helperText('Обычно /chat/completions. Изменяйте только если документация провайдера требует другой путь.'),
                        Forms\Components\TextInput::make('max_output_tokens')
                            ->label('Максимум токенов ответа')
                            ->numeric()->minValue(256)->maxValue(32768)->required(),
                        Forms\Components\TextInput::make('retry_times')
                            ->label('Повторы HTTP-запроса')
                            ->numeric()->minValue(0)->maxValue(5)->required(),
                        Forms\Components\TextInput::make('retry_sleep_ms')
                            ->label('Пауза между повторами, мс')
                            ->numeric()->minValue(0)->maxValue(10000)->required(),
                        Forms\Components\TextInput::make('requests_per_minute')
                            ->label('Лимит запросов в минуту')
                            ->numeric()->minValue(1)->maxValue(10000)->required(),
                        Forms\Components\TextInput::make('rate_limit_safety_seconds')
                            ->label('Запас между запросами, секунд')
                            ->numeric()->minValue(0)->maxValue(30)->required(),
                        Forms\Components\TextInput::make('hourly_request_limit')
                            ->label('Максимум запросов в час')
                            ->helperText('После достижения лимита новые товары переходят на ручную проверку без обращения к API.')
                            ->numeric()->minValue(1)->maxValue(100000)->required(),
                        Forms\Components\TextInput::make('daily_request_limit')
                            ->label('Максимум запросов в сутки')
                            ->helperText('Защищает от неожиданного расхода квоты или средств.')
                            ->numeric()->minValue(1)->maxValue(1000000)->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Модерация изображений')
                    ->description('Одна универсальная схема подходит для Gemini, OpenAI-compatible и native moderation endpoint.')
                    ->schema([
                        Forms\Components\Select::make('image_provider')
                            ->label('Формат API')
                            ->options([
                                'gemini' => 'Gemini API',
                                'openai_compatible' => 'OpenAI-compatible API',
                                'openai_moderation' => 'Native moderation endpoint',
                            ])
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                                if ($state === 'gemini') {
                                    $set('image_model', 'gemini-3.1-flash-lite');
                                    $set('image_base_url', 'https://generativelanguage.googleapis.com/v1beta');
                                    $set('image_endpoint_path', '/models/{model}:generateContent');
                                    $set('image_auth_header', 'x-goog-api-key');
                                    $set('image_auth_prefix', '');
                                }

                                if ($state === 'openai_compatible') {
                                    $set('image_model', 'gpt-4o-mini');
                                    $set('image_base_url', 'https://api.openai.com/v1');
                                    $set('image_endpoint_path', '/chat/completions');
                                    $set('image_auth_header', 'Authorization');
                                    $set('image_auth_prefix', 'Bearer');
                                }

                                if ($state === 'openai_moderation') {
                                    $set('image_model', 'omni-moderation-latest');
                                    $set('image_base_url', 'https://api.openai.com/v1');
                                    $set('image_endpoint_path', '/moderations');
                                    $set('image_auth_header', 'Authorization');
                                    $set('image_auth_prefix', 'Bearer');
                                }
                            })
                            ->required(),
                        Forms\Components\Toggle::make('image_moderation_enabled')
                            ->label('Включить проверку фото')
                            ->helperText('Если включено, товар не будет публиковаться, пока фото не пройдут проверку.'),
                        Forms\Components\TextInput::make('image_api_key')
                            ->label('API-ключ')
                            ->password()->revealable()->autocomplete(false)
                            ->helperText('Можно оставить пустым, если ключ уже сохранён в настройках.'),
                        Forms\Components\TextInput::make('image_model')
                            ->label('Модель')
                            ->required()
                            ->helperText('Например: gemini-3.1-flash-lite, qwen/qwen3.6-flash, gpt-4o-mini.'),
                        Forms\Components\TextInput::make('image_base_url')
                            ->label('Базовый URL API')
                            ->url()->required()
                            ->rule(function (AiProviderUrlValidator $validator): \Closure {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($validator): void {
                                    try {
                                        $validator->validate((string) $value);
                                    } catch (\InvalidArgumentException $exception) {
                                        $fail($exception->getMessage());
                                    }
                                };
                            })
                            ->helperText('Например: https://api.openai.com/v1 или https://generativelanguage.googleapis.com/v1beta.'),
                        Forms\Components\TextInput::make('image_endpoint_path')
                            ->label('Путь endpoint')
                            ->required()
                            ->helperText('Можно использовать {model}. Пример: /chat/completions, /moderations или /models/{model}:generateContent.'),
                        Forms\Components\TextInput::make('image_auth_header')
                            ->label('Заголовок авторизации')
                            ->required()
                            ->helperText('Например: Authorization или x-goog-api-key.'),
                        Forms\Components\TextInput::make('image_auth_prefix')
                            ->label('Префикс ключа')
                            ->placeholder('Bearer')
                            ->helperText('Например: Bearer. Для x-goog-api-key оставьте пустым.'),
                        Forms\Components\TextInput::make('image_queue')
                            ->label('Очередь проверки фото')
                            ->required()
                            ->helperText('Обычно moderation.'),
                        Forms\Components\TextInput::make('image_timeout')
                            ->label('Таймаут фото, секунд')
                            ->numeric()->minValue(5)->maxValue(300)->required(),
                        Forms\Components\TextInput::make('image_max_attempts')
                            ->label('Попытки проверки фото')
                            ->numeric()->minValue(1)->maxValue(10)->required(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(ModerationSettingsWriter $writer, ModerationSettingsSnapshotFactory $factory): void
    {
        $data = $this->form->getState();
        $writer->save($data);
        $this->applySnapshot($factory->make());

        Notification::make()
            ->title('Настройки сохранены')
            ->body('Новые параметры применятся к следующим заданиям модерации.')
            ->success()
            ->send();

        activity('moderation_settings')
            ->causedBy(auth()->user())
            ->withProperties([
                'text_provider' => $data['provider'],
                'image_provider' => $data['image_provider'],
                'mode' => $data['mode'],
                'ai_enabled' => (bool) $data['ai_enabled'],
                'image_enabled' => (bool) $data['image_moderation_enabled'],
            ])
            ->log('Изменены настройки AI-модерации');
    }

    private function applySnapshot(ModerationSettingsSnapshot $snapshot): void
    {
        $this->data = $snapshot->data;
        $this->apiKeyConfigured = $snapshot->apiKeyConfigured;
        $this->budgetUsage = $snapshot->budgetUsage;
        $this->operationsStatus = $snapshot->operationsStatus;

        $this->form->fill($snapshot->data);
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
