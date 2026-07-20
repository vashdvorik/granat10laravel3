<?php

namespace App\Services;

use App\Contracts\AiModerationClient;
use App\Contracts\ProductImageModerationClient;
use RuntimeException;

class ModerationClientResolver
{
    public function text(string $provider): AiModerationClient
    {
        return match ($provider) {
            'fake' => app(FakeAiModerationClient::class),
            'google' => app(GoogleGeminiModerationClient::class),
            'openai_compatible', 'deepseek' => app(OpenAiCompatibleModerationClient::class),
            default => throw new RuntimeException('Unsupported AI moderation provider.'),
        };
    }

    public function images(string $provider): ProductImageModerationClient
    {
        return app(UniversalImageModerationClient::class);
    }
}
