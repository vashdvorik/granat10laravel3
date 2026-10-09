<?php

namespace App\Services;

use App\Data\ModerationProvider;
use App\Data\ModerationSettingsSnapshot;

/** Сохранённые настройки ИИ-модерации в виде, удобном для формы в админке. */
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

        $apiKeyConfigured = $this->secrets->has()
            || filled($values['OPENROUTER_API_KEY'] ?? null)
            || filled(config('moderation.openrouter.api_key'));

        return new ModerationSettingsSnapshot(
            data: [
                'queue_connection' => $values['QUEUE_CONNECTION'] ?: 'database',
                'ai_enabled' => filter_var($values['PRODUCT_MODERATION_AI_ENABLED'], FILTER_VALIDATE_BOOL),
                'mode' => $values['PRODUCT_MODERATION_AI_MODE'] ?: 'shadow',
                'queue' => $values['PRODUCT_MODERATION_QUEUE'] ?: 'moderation',
                'timeout' => (int) ($values['PRODUCT_MODERATION_TIMEOUT'] ?: 60),
                'max_attempts' => (int) ($values['PRODUCT_MODERATION_MAX_ATTEMPTS'] ?: 3),
                'max_risk' => round((float) ($values['PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK'] ?: 0.10) * 100, 2),
                'excluded_categories' => $values['PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES'] ?? '',
                'text_model' => $values['OPENROUTER_TEXT_MODEL'] ?: (string) config('moderation.openrouter.text_model'),
                'image_model' => $values['OPENROUTER_IMAGE_MODEL'] ?: (string) config('moderation.openrouter.image_model'),
                'max_output_tokens' => (int) ($values['AI_MODERATION_MAX_OUTPUT_TOKENS'] ?: 1200),
                'retry_times' => (int) ($values['AI_MODERATION_RETRY_TIMES'] ?? 0),
                'retry_sleep_ms' => (int) ($values['AI_MODERATION_RETRY_SLEEP_MS'] ?: 500),
                'requests_per_minute' => (int) ($values['AI_MODERATION_REQUESTS_PER_MINUTE'] ?: 5),
                'rate_limit_safety_seconds' => (int) ($values['AI_MODERATION_RATE_LIMIT_SAFETY_SECONDS'] ?: 1),
                'hourly_request_limit' => (int) ($values['AI_MODERATION_HOURLY_REQUEST_LIMIT'] ?: 60),
                'daily_request_limit' => (int) ($values['AI_MODERATION_DAILY_REQUEST_LIMIT'] ?: 500),
                'image_moderation_enabled' => filter_var($values['AI_IMAGE_MODERATION_ENABLED'], FILTER_VALIDATE_BOOL),
                'image_queue' => $values['AI_IMAGE_MODERATION_QUEUE'] ?: ($values['PRODUCT_MODERATION_QUEUE'] ?: 'moderation'),
                'image_timeout' => (int) ($values['AI_IMAGE_MODERATION_TIMEOUT'] ?: $values['PRODUCT_MODERATION_TIMEOUT'] ?: 60),
                'image_max_attempts' => (int) ($values['AI_IMAGE_MODERATION_MAX_ATTEMPTS'] ?: $values['PRODUCT_MODERATION_MAX_ATTEMPTS'] ?: 3),
            ],
            apiKeyConfigured: $apiKeyConfigured,
            budgetUsage: $this->budget->usage(ModerationProvider::TEXT_SCOPE),
            operationsStatus: $this->operationsStatus->current(),
        );
    }
}
