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
        public bool $apiKeyConfigured,
        public bool $textApiKeyConfigured,
        public bool $imageApiKeyConfigured,
        public array $budgetUsage,
        public array $operationsStatus,
    ) {}
}
