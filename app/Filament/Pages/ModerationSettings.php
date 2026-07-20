<?php

namespace App\Filament\Pages;

use App\Data\ModerationSettingsSnapshot;
use App\Services\AiModerationRequestBudget;
use App\Services\AiProviderUrlValidator;
use App\Services\ModerationOperationsStatus;
use App\Services\ModerationSettingsSnapshotFactory;
use App\Services\ModerationSettingsWriter;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Gate;

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
                    ->description('Отдельная проверка фото товара. Нужна модель, которая умеет анализировать изображения.')
                    ->schema([
                        Forms\Components\Select::make('image_provider')
                            ->label('��������� ��������� �����������')
                            ->options([
                                'google' => 'Google Gemini vision',
                                'openai_moderation' => 'OpenAI Moderation',
                            ])
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                                if ($state === 'openai_moderation') {
                                    $set('image_model', 'omni-moderation-latest');
                                    $set('image_base_url', 'https://api.openai.com/v1');
                                }

                                if ($state === 'google') {
                                    $set('image_model', 'gemini-2.5-flash');
                                    $set('image_base_url', 'https://generativelanguage.googleapis.com/v1beta');
                                }
                            })
                            ->required(),
                        Forms\Components\Toggle::make('image_moderation_enabled')
                            ->label('Включить проверку фото')
                            ->helperText('Если включено, товар не будет автопубликован, пока фото не пройдут проверку.'),
                        Forms\Components\TextInput::make('image_api_key')
                            ->label('Новый API-ключ для фото')
                            ->password()->revealable()->autocomplete(false)
                            ->helperText('Можно оставить пустым: тогда сохранится текущий ключ для проверки фото.'),
                        Forms\Components\TextInput::make('image_model')
                            ->label('Модель для проверки фото')
                            ->required()
                            ->helperText('Нужна vision-модель. Например: gemini-2.5-flash.'),
                        Forms\Components\TextInput::make('image_base_url')
                            ->label('Базовый URL API для фото')
                            ->url()->required()
                            ->rule(function (AiProviderUrlValidator $validator): \Closure {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($validator): void {
                                    try {
                                        $validator->validate((string) $value);
                                    } catch (\InvalidArgumentException $exception) {
                                        $fail($exception->getMessage());
                                    }
                                };
                            }),
                        Forms\Components\TextInput::make('image_queue')
                            ->label('Очередь проверки фото')
                            ->required()
                            ->helperText('Можно оставить moderation, чтобы один worker обрабатывал текст и фото.'),
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
}





