<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;

class ModerationSettingsWriter
{
    public function __construct(
        private readonly EnvironmentSettingsService $settings,
        private readonly ModerationSecretStore $secrets,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        $provider = (string) ($data['provider'] ?? 'google');
        $imageProvider = (string) ($data['image_provider'] ?? 'google');
        $nativeOpenAiImageModeration = $imageProvider === 'openai_moderation';
        $imageModel = $nativeOpenAiImageModeration ? 'omni-moderation-latest' : ($data['image_model'] ?? 'gemini-2.5-flash');
        $imageBaseUrl = $nativeOpenAiImageModeration ? 'https://api.openai.com/v1' : ($data['image_base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta');

        if ($provider === 'google' && (str_starts_with((string) ($data['model'] ?? ''), 'deepseek-') || str_contains((string) ($data['base_url'] ?? ''), 'deepseek.com'))) {
            throw ValidationException::withMessages([
                'data.provider' => 'Для моделей DeepSeek выберите провайдера DeepSeek.',
            ]);
        }

        if ($provider === 'deepseek') {
            $data['base_url'] = 'https://api.deepseek.com';
            $data['chat_completions_path'] = '/chat/completions';
        }

        $textPrefix = $this->textEnvironmentPrefix($provider);
        $imagePrefix = $imageProvider === 'google' ? 'AI_IMAGE_GOOGLE' : 'AI_IMAGE_OPENAI';
        $textKeyChanged = filled($data['api_key'] ?? null);
        $imageKeyChanged = filled($data['image_api_key'] ?? null);
        $textKeyAvailable = filled($data['api_key'] ?? null)
            || $this->secrets->has($this->textSecretName($provider))
            || $this->settings->hasValue($textPrefix.'_API_KEY')
            || $this->settings->hasValue('AI_MODERATION_API_KEY')
            || ($provider === 'google' && $this->settings->hasValue('GEMINI_API_KEY'));
        $imageKeyAvailable = filled($data['image_api_key'] ?? null)
            || $this->secrets->has($this->imageSecretName($imageProvider))
            || $this->settings->hasValue($imagePrefix.'_API_KEY')
            || $this->settings->hasValue('AI_IMAGE_MODERATION_API_KEY');

        if ($data['ai_enabled'] && ! $textKeyAvailable) {
            throw ValidationException::withMessages([
                'data.api_key' => 'Для включения текстовой ИИ-модерации укажите ключ выбранного провайдера.',
            ]);
        }

        if ($data['image_moderation_enabled'] && ! $imageKeyAvailable) {
            throw ValidationException::withMessages([
                'data.image_api_key' => 'Для включения проверки изображений укажите ключ выбранного провайдера.',
            ]);
        }

        $values = [
            'QUEUE_CONNECTION' => $data['queue_connection'],
            'PRODUCT_MODERATION_AI_ENABLED' => (bool) $data['ai_enabled'],
            'PRODUCT_MODERATION_AI_PROVIDER' => $provider,
            'PRODUCT_MODERATION_AI_MODE' => $data['mode'] ?? 'shadow',
            'PRODUCT_MODERATION_QUEUE' => $data['queue'] ?? 'moderation',
            'PRODUCT_MODERATION_TIMEOUT' => $data['timeout'] ?? 60,
            'PRODUCT_MODERATION_MAX_ATTEMPTS' => $data['max_attempts'] ?? 3,
            'PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK' => round((float) ($data['max_risk'] ?? 10) / 100, 4),
            'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES' => $data['excluded_categories'] ?? '',
            'AI_MODERATION_MODEL' => $data['model'] ?? '',
            'AI_MODERATION_BASE_URL' => $data['base_url'] ?? '',
            'AI_MODERATION_CHAT_COMPLETIONS_PATH' => $data['chat_completions_path'] ?? '/chat/completions',
            'AI_MODERATION_MAX_OUTPUT_TOKENS' => $data['max_output_tokens'] ?? 1200,
            'AI_MODERATION_RETRY_TIMES' => $data['retry_times'] ?? 0,
            'AI_MODERATION_RETRY_SLEEP_MS' => $data['retry_sleep_ms'] ?? 500,
            'AI_MODERATION_REQUESTS_PER_MINUTE' => $data['requests_per_minute'] ?? 5,
            'AI_MODERATION_RATE_LIMIT_SAFETY_SECONDS' => $data['rate_limit_safety_seconds'] ?? 1,
            'AI_MODERATION_HOURLY_REQUEST_LIMIT' => $data['hourly_request_limit'] ?? 60,
            'AI_MODERATION_DAILY_REQUEST_LIMIT' => $data['daily_request_limit'] ?? 500,
            'AI_IMAGE_MODERATION_ENABLED' => (bool) $data['image_moderation_enabled'],
            'AI_IMAGE_MODERATION_PROVIDER' => $imageProvider,
            'AI_IMAGE_MODERATION_QUEUE' => $data['image_queue'] ?? 'moderation',
            'AI_IMAGE_MODERATION_TIMEOUT' => $data['image_timeout'] ?? 60,
            'AI_IMAGE_MODERATION_MAX_ATTEMPTS' => $data['image_max_attempts'] ?? 3,
            'AI_IMAGE_MODERATION_MODEL' => $imageModel,
            'AI_IMAGE_MODERATION_BASE_URL' => $imageBaseUrl,
            $textPrefix.'_MODEL' => $data['model'] ?? '',
            $textPrefix.'_BASE_URL' => $data['base_url'] ?? '',
            'AI_TEXT_OPENAI_COMPATIBLE_CHAT_COMPLETIONS_PATH' => $data['chat_completions_path'] ?? '/chat/completions',
            $imagePrefix.'_MODEL' => $imageModel,
            $imagePrefix.'_BASE_URL' => $imageBaseUrl,
        ];

        if ($provider !== 'google') {
            $values[$textPrefix.'_CHAT_COMPLETIONS_PATH'] = $data['chat_completions_path'] ?? '/chat/completions';
        }

        if ($textKeyChanged) {
            $this->secrets->put($this->textSecretName($provider), (string) $data['api_key']);
        }

        if ($imageKeyChanged) {
            $this->secrets->put($this->imageSecretName($imageProvider), (string) $data['image_api_key']);
        }

        $this->settings->update($values);
        Artisan::call('config:clear');
        Artisan::call('queue:restart');
        $this->secrets->applyToConfig();
    }

    private function textSecretName(string $provider): string
    {
        return 'text.'.match ($provider) {
            'google' => 'google',
            'deepseek' => 'deepseek',
            default => 'openai_compatible',
        };
    }

    private function textEnvironmentPrefix(string $provider): string
    {
        return match ($provider) {
            'google' => 'AI_TEXT_GOOGLE',
            'deepseek' => 'AI_TEXT_DEEPSEEK',
            default => 'AI_TEXT_OPENAI_COMPATIBLE',
        };
    }

    private function imageSecretName(string $provider): string
    {
        return 'image.'.($provider === 'google' ? 'google' : 'openai_moderation');
    }
}
