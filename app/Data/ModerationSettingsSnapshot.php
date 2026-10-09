<?php

namespace App\Data;

class ModerationSettingsSnapshot
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $budgetUsage
     * @param  array<string, mixed>  $operationsStatus
     */
    public function __construct(
        public array $data,
        /** Ключ OpenRouter задан (в базе или в .env). Он один на текст и на фото. */
        public bool $apiKeyConfigured,
        public array $budgetUsage,
        public array $operationsStatus,
    ) {}
}
