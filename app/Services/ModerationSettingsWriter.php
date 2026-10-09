<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;

/** Записывает настройки ИИ-модерации в .env, а ключ OpenRouter — в базу в зашифрованном виде. */
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
        $newKey = trim((string) ($data['api_key'] ?? ''));

        $keyAvailable = $newKey !== ''
            || $this->secrets->has()
            || $this->settings->hasValue('OPENROUTER_API_KEY');

        if (! $keyAvailable && ($data['ai_enabled'] || $data['image_moderation_enabled'])) {
            throw ValidationException::withMessages([
                'data.api_key' => 'Чтобы включить проверку ИИ, укажите ключ OpenRouter.',
            ]);
        }

        $values = [
            'QUEUE_CONNECTION' => $data['queue_connection'],
            'PRODUCT_MODERATION_AI_ENABLED' => (bool) $data['ai_enabled'],
            'PRODUCT_MODERATION_AI_MODE' => $data['mode'] ?? 'shadow',
            'PRODUCT_MODERATION_QUEUE' => $data['queue'] ?? 'moderation',
            'PRODUCT_MODERATION_TIMEOUT' => $data['timeout'] ?? 60,
            'PRODUCT_MODERATION_MAX_ATTEMPTS' => $data['max_attempts'] ?? 3,
            'PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK' => round((float) ($data['max_risk'] ?? 10) / 100, 4),
            'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES' => $data['excluded_categories'] ?? '',
            'OPENROUTER_TEXT_MODEL' => trim((string) ($data['text_model'] ?? '')),
            'OPENROUTER_IMAGE_MODEL' => trim((string) ($data['image_model'] ?? '')),
            'AI_MODERATION_MAX_OUTPUT_TOKENS' => $data['max_output_tokens'] ?? 1200,
            'AI_MODERATION_RETRY_TIMES' => $data['retry_times'] ?? 0,
            'AI_MODERATION_RETRY_SLEEP_MS' => $data['retry_sleep_ms'] ?? 500,
            'AI_MODERATION_REQUESTS_PER_MINUTE' => $data['requests_per_minute'] ?? 5,
            'AI_MODERATION_RATE_LIMIT_SAFETY_SECONDS' => $data['rate_limit_safety_seconds'] ?? 1,
            'AI_MODERATION_HOURLY_REQUEST_LIMIT' => $data['hourly_request_limit'] ?? 60,
            'AI_MODERATION_DAILY_REQUEST_LIMIT' => $data['daily_request_limit'] ?? 500,
            'AI_IMAGE_MODERATION_ENABLED' => (bool) $data['image_moderation_enabled'],
            'AI_IMAGE_MODERATION_QUEUE' => $data['image_queue'] ?? 'moderation',
            'AI_IMAGE_MODERATION_TIMEOUT' => $data['image_timeout'] ?? 60,
            'AI_IMAGE_MODERATION_MAX_ATTEMPTS' => $data['image_max_attempts'] ?? 3,
        ];

        if ($newKey !== '') {
            $this->secrets->put($newKey);
        }

        $this->settings->update($values);
        Artisan::call('config:clear');
        Artisan::call('queue:restart');
        $this->secrets->applyToConfig();
    }
}
