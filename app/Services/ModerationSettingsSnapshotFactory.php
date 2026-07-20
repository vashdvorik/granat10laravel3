<?php

namespace App\Services;

use App\Data\ModerationSettingsSnapshot;

class ModerationSettingsSnapshotFactory
{
    public function __construct(
        private readonly EnvironmentSettingsService $settings,
        private readonly ModerationSecretStore $secrets,
        private readonly AiModerationRequestBudget $budget,
        private readonly ModerationOperationsStatus $operationsStatus,
    ) {}

    public function make(): ModerationSettingsSnapshot
    {
        $values = $this->settings->values();
        $provider = $this->resolveTextProvider($values);
        $imageProvider = $this->resolveImageProvider($values);
        $textPrefix = $this->textEnvironmentPrefix($provider);
        $imagePrefix = $imageProvider === 'google' ? 'AI_IMAGE_GOOGLE' : 'AI_IMAGE_OPENAI';
        $legacyGoogle = $provider === 'google';
        $nativeOpenAiImageModeration = $imageProvider === 'openai_moderation';

        $textApiKeyConfigured = $this->secrets->has($this->textSecretName($provider))
            || filled($values[$textPrefix.'_API_KEY'] ?? null)
            || ($provider === 'google' && filled($values['GEMINI_API_KEY'] ?? null))
            || filled($values['AI_MODERATION_API_KEY'] ?? null);
        $imageApiKeyConfigured = $this->secrets->has($this->imageSecretName($imageProvider))
            || filled($values[$imagePrefix.'_API_KEY'] ?? null)
            || filled($values['AI_IMAGE_MODERATION_API_KEY'] ?? null);

        return new ModerationSettingsSnapshot(
            data: [
                'queue_connection' => $values['QUEUE_CONNECTION'] ?: 'database',
                'ai_enabled' => filter_var($values['PRODUCT_MODERATION_AI_ENABLED'], FILTER_VALIDATE_BOOL),
                'provider' => $provider === 'fake' ? 'google' : $provider,
                'mode' => $values['PRODUCT_MODERATION_AI_MODE'] ?: 'shadow',
                'queue' => $values['PRODUCT_MODERATION_QUEUE'] ?: 'moderation',
                'timeout' => (int) ($values['PRODUCT_MODERATION_TIMEOUT'] ?: 60),
                'max_attempts' => (int) ($values['PRODUCT_MODERATION_MAX_ATTEMPTS'] ?: 3),
                'max_risk' => round((float) ($values['PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK'] ?: 0.10) * 100, 2),
                'excluded_categories' => $values['PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES'] ?? '',
                'model' => ($values[$textPrefix.'_MODEL'] ?? null) ?: $values['AI_MODERATION_MODEL']
                    ?: ($legacyGoogle ? ($values['GEMINI_MODEL'] ?: 'gemini-3.5-flash') : ''),
                'base_url' => ($values[$textPrefix.'_BASE_URL'] ?? null) ?: $values['AI_MODERATION_BASE_URL']
                    ?: ($legacyGoogle ? ($values['GEMINI_BASE_URL'] ?: 'https://generativelanguage.googleapis.com/v1beta') : 'https://api.openai.com/v1'),
                'chat_completions_path' => ($values[$textPrefix.'_CHAT_COMPLETIONS_PATH'] ?? null) ?: ($values['AI_MODERATION_CHAT_COMPLETIONS_PATH'] ?: '/chat/completions'),
                'max_output_tokens' => (int) ($values['AI_MODERATION_MAX_OUTPUT_TOKENS'] ?: $values['GEMINI_MAX_OUTPUT_TOKENS'] ?: 1200),
                'retry_times' => (int) ($values['AI_MODERATION_RETRY_TIMES'] ?? $values['GEMINI_RETRY_TIMES'] ?? 0),
                'retry_sleep_ms' => (int) ($values['AI_MODERATION_RETRY_SLEEP_MS'] ?: $values['GEMINI_RETRY_SLEEP_MS'] ?: 500),
                'requests_per_minute' => (int) ($values['AI_MODERATION_REQUESTS_PER_MINUTE'] ?: $values['GEMINI_REQUESTS_PER_MINUTE'] ?: 5),
                'rate_limit_safety_seconds' => (int) ($values['AI_MODERATION_RATE_LIMIT_SAFETY_SECONDS'] ?: $values['GEMINI_RATE_LIMIT_SAFETY_SECONDS'] ?: 1),
                'hourly_request_limit' => (int) ($values['AI_MODERATION_HOURLY_REQUEST_LIMIT'] ?: 60),
                'daily_request_limit' => (int) ($values['AI_MODERATION_DAILY_REQUEST_LIMIT'] ?: 500),
                'image_moderation_enabled' => filter_var($values['AI_IMAGE_MODERATION_ENABLED'], FILTER_VALIDATE_BOOL),
                'image_provider' => $imageProvider,
                'image_queue' => $values['AI_IMAGE_MODERATION_QUEUE'] ?: ($values['PRODUCT_MODERATION_QUEUE'] ?: 'moderation'),
                'image_timeout' => (int) ($values['AI_IMAGE_MODERATION_TIMEOUT'] ?: $values['PRODUCT_MODERATION_TIMEOUT'] ?: 60),
                'image_max_attempts' => (int) ($values['AI_IMAGE_MODERATION_MAX_ATTEMPTS'] ?: $values['PRODUCT_MODERATION_MAX_ATTEMPTS'] ?: 3),
                'image_model' => $nativeOpenAiImageModeration
                    ? 'omni-moderation-latest'
                    : (($values['AI_IMAGE_GOOGLE_MODEL'] ?? null) ?: ($values['AI_IMAGE_MODERATION_MODEL'] ?: 'gemini-2.5-flash')),
                'image_base_url' => $nativeOpenAiImageModeration
                    ? 'https://api.openai.com/v1'
                    : (($values['AI_IMAGE_GOOGLE_BASE_URL'] ?? null) ?: ($values['AI_IMAGE_MODERATION_BASE_URL'] ?: 'https://generativelanguage.googleapis.com/v1beta')),
            ],
            apiKeyConfigured: $textApiKeyConfigured || $imageApiKeyConfigured,
            textApiKeyConfigured: $textApiKeyConfigured,
            imageApiKeyConfigured: $imageApiKeyConfigured,
            budgetUsage: $this->budget->usage('text:'.$provider),
            operationsStatus: $this->operationsStatus->current(),
        );
    }

    /** @param array<string, mixed> $values */
    private function resolveTextProvider(array $values): string
    {
        $provider = (string) ($values['PRODUCT_MODERATION_AI_PROVIDER'] ?: 'google');
        $legacyTextModel = (string) ($values['AI_MODERATION_MODEL'] ?? '');
        $legacyTextUrl = (string) ($values['AI_MODERATION_BASE_URL'] ?? '');

        if ($provider === 'google' && (str_starts_with($legacyTextModel, 'deepseek-') || str_contains($legacyTextUrl, 'deepseek.com'))) {
            return 'deepseek';
        }

        return $provider;
    }

    /** @param array<string, mixed> $values */
    private function resolveImageProvider(array $values): string
    {
        return (string) ($values['AI_IMAGE_MODERATION_PROVIDER'] ?: 'google');
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
