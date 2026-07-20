<?php

namespace App\Services;

use App\Data\ModerationProviderMetadata;

class ModerationProviderMetadataResolver
{
    public function resolve(): ModerationProviderMetadata
    {
        $provider = (string) config('moderation.provider');

        return new ModerationProviderMetadata(
            provider: $provider,
            model: match ($provider) {
                'google' => (string) config('moderation.google.model'),
                'openai_compatible' => (string) config('moderation.openai_compatible.model'),
                'deepseek' => (string) config('moderation.deepseek.model'),
                'fake' => 'fake',
                default => 'unknown',
            },
            promptVersion: (string) config('moderation.audit.prompt_version'),
            policyVersion: (string) config('moderation.audit.policy_version'),
        );
    }
}
