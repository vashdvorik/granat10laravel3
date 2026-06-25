<?php

namespace App\Filament\Pages;

use App\Services\AiModerationRequestBudget;
use App\Services\AiProviderUrlValidator;
use App\Services\EnvironmentSettingsService;
use App\Services\ModerationOperationsStatus;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
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

    public function mount(EnvironmentSettingsService $settings): void
    {
        $values = $settings->values();
        $this->apiKeyConfigured = $settings->hasValue('AI_MODERATION_API_KEY')
            || $settings->hasValue('AI_IMAGE_MODERATION_API_KEY')
            || $settings->hasValue('GEMINI_API_KEY');
        $this->budgetUsage = app(AiModerationRequestBudget::class)->usage();
        $this->operationsStatus = app(ModerationOperationsStatus::class)->current();

        $provider = $values['PRODUCT_MODERATION_AI_PROVIDER'] ?: 'google';
        $legacyGoogle = $provider === 'google';

        $this->form->fill([
            'queue_connection' => $values['QUEUE_CONNECTION'] ?: 'database',
            'ai_enabled' => filter_var($values['PRODUCT_MODERATION_AI_ENABLED'], FILTER_VALIDATE_BOOL),
            'provider' => $provider === 'fake' ? 'google' : $provider,
            'mode' => $values['PRODUCT_MODERATION_AI_MODE'] ?: 'shadow',
            'queue' => $values['PRODUCT_MODERATION_QUEUE'] ?: 'moderation',
            'timeout' => (int) ($values['PRODUCT_MODERATION_TIMEOUT'] ?: 60),
            'max_attempts' => (int) ($values['PRODUCT_MODERATION_MAX_ATTEMPTS'] ?: 3),
            'max_risk' => round((float) ($values['PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK'] ?: 0.10) * 100, 2),
            'excluded_categories' => $values['PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES'] ?? '',
            'model' => $values['AI_MODERATION_MODEL']
                ?: ($legacyGoogle ? ($values['GEMINI_MODEL'] ?: 'gemini-3.5-flash') : ''),
            'base_url' => $values['AI_MODERATION_BASE_URL']
                ?: ($legacyGoogle ? ($values['GEMINI_BASE_URL'] ?: 'https://generativelanguage.googleapis.com/v1beta') : 'https://api.openai.com/v1'),
            'chat_completions_path' => $values['AI_MODERATION_CHAT_COMPLETIONS_PATH'] ?: '/chat/completions',
            'max_output_tokens' => (int) ($values['AI_MODERATION_MAX_OUTPUT_TOKENS'] ?: $values['GEMINI_MAX_OUTPUT_TOKENS'] ?: 1200),
            'retry_times' => (int) ($values['AI_MODERATION_RETRY_TIMES'] ?? $values['GEMINI_RETRY_TIMES'] ?? 0),
            'retry_sleep_ms' => (int) ($values['AI_MODERATION_RETRY_SLEEP_MS'] ?: $values['GEMINI_RETRY_SLEEP_MS'] ?: 500),
            'requests_per_minute' => (int) ($values['AI_MODERATION_REQUESTS_PER_MINUTE'] ?: $values['GEMINI_REQUESTS_PER_MINUTE'] ?: 5),
            'rate_limit_safety_seconds' => (int) ($values['AI_MODERATION_RATE_LIMIT_SAFETY_SECONDS'] ?: $values['GEMINI_RATE_LIMIT_SAFETY_SECONDS'] ?: 1),
            'hourly_request_limit' => (int) ($values['AI_MODERATION_HOURLY_REQUEST_LIMIT'] ?: 60),
            'daily_request_limit' => (int) ($values['AI_MODERATION_DAILY_REQUEST_LIMIT'] ?: 500),
            'image_moderation_enabled' => filter_var($values['AI_IMAGE_MODERATION_ENABLED'], FILTER_VALIDATE_BOOL),
            'image_queue' => $values['AI_IMAGE_MODERATION_QUEUE'] ?: ($values['PRODUCT_MODERATION_QUEUE'] ?: 'moderation'),
            'image_timeout' => (int) ($values['AI_IMAGE_MODERATION_TIMEOUT'] ?: $values['PRODUCT_MODERATION_TIMEOUT'] ?: 60),
            'image_max_attempts' => (int) ($values['AI_IMAGE_MODERATION_MAX_ATTEMPTS'] ?: $values['PRODUCT_MODERATION_MAX_ATTEMPTS'] ?: 3),
            'image_model' => $values['AI_IMAGE_MODERATION_MODEL'] ?: ($values['AI_MODERATION_MODEL'] ?: 'gemini-2.5-flash'),
            'image_base_url' => $values['AI_IMAGE_MODERATION_BASE_URL'] ?: ($values['AI_MODERATION_BASE_URL'] ?: 'https://generativelanguage.googleapis.com/v1beta'),
        ]);
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
                                'openai_compatible' => 'OpenAI-compatible API',
                            ])
                            ->live()
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
                        Forms\Components\Toggle::make('image_moderation_enabled')
                            ->label('Включить проверку фото')
                            ->helperText('Если включено, товар не будет автопубликован, пока фото не пройдут проверку.'),
                        Forms\Components\TextInput::make('image_api_key')
                            ->label('Новый API-ключ для фото')
                            ->password()->revealable()->autocomplete(false)
                            ->helperText('Можно оставить пустым: тогда сохранится текущий ключ или будет использован основной ключ провайдера.'),
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

    public function save(EnvironmentSettingsService $settings): void
    {
        $data = $this->form->getState();
        $values = [
            'QUEUE_CONNECTION' => $data['queue_connection'],
            'PRODUCT_MODERATION_AI_ENABLED' => (bool) $data['ai_enabled'],
            'PRODUCT_MODERATION_AI_PROVIDER' => $data['provider'],
            'PRODUCT_MODERATION_AI_MODE' => $data['mode'],
            'PRODUCT_MODERATION_QUEUE' => $data['queue'],
            'PRODUCT_MODERATION_TIMEOUT' => $data['timeout'],
            'PRODUCT_MODERATION_MAX_ATTEMPTS' => $data['max_attempts'],
            'PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK' => round((float) $data['max_risk'] / 100, 4),
            'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES' => $data['excluded_categories'] ?? '',
            'AI_MODERATION_MODEL' => $data['model'],
            'AI_MODERATION_BASE_URL' => $data['base_url'],
            'AI_MODERATION_CHAT_COMPLETIONS_PATH' => $data['chat_completions_path'] ?? '/chat/completions',
            'AI_MODERATION_MAX_OUTPUT_TOKENS' => $data['max_output_tokens'],
            'AI_MODERATION_RETRY_TIMES' => $data['retry_times'],
            'AI_MODERATION_RETRY_SLEEP_MS' => $data['retry_sleep_ms'],
            'AI_MODERATION_REQUESTS_PER_MINUTE' => $data['requests_per_minute'],
            'AI_MODERATION_RATE_LIMIT_SAFETY_SECONDS' => $data['rate_limit_safety_seconds'],
            'AI_MODERATION_HOURLY_REQUEST_LIMIT' => $data['hourly_request_limit'],
            'AI_MODERATION_DAILY_REQUEST_LIMIT' => $data['daily_request_limit'],
            'AI_IMAGE_MODERATION_ENABLED' => (bool) $data['image_moderation_enabled'],
            'AI_IMAGE_MODERATION_PROVIDER' => 'google',
            'AI_IMAGE_MODERATION_QUEUE' => $data['image_queue'],
            'AI_IMAGE_MODERATION_TIMEOUT' => $data['image_timeout'],
            'AI_IMAGE_MODERATION_MAX_ATTEMPTS' => $data['image_max_attempts'],
            'AI_IMAGE_MODERATION_MODEL' => $data['image_model'],
            'AI_IMAGE_MODERATION_BASE_URL' => $data['image_base_url'],
        ];

        if (filled($data['api_key'] ?? null)) {
            $values['AI_MODERATION_API_KEY'] = $data['api_key'];
        }

        if (filled($data['image_api_key'] ?? null)) {
            $values['AI_IMAGE_MODERATION_API_KEY'] = $data['image_api_key'];
        }

        $settings->update($values);
        Artisan::call('config:clear');
        $this->apiKeyConfigured = $settings->hasValue('AI_MODERATION_API_KEY')
            || $settings->hasValue('AI_IMAGE_MODERATION_API_KEY')
            || $settings->hasValue('GEMINI_API_KEY');
        $this->data['api_key'] = null;
        $this->data['image_api_key'] = null;

        Notification::make()
            ->title('Настройки сохранены')
            ->body('Новые параметры применятся к следующим заданиям модерации.')
            ->success()
            ->send();
    }
}
