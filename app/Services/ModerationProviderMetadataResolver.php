<?php

namespace App\Services;

use App\Data\ModerationProvider;
use App\Data\ModerationProviderMetadata;

class ModerationProviderMetadataResolver
{
    public function resolve(): ModerationProviderMetadata
    {
        return new ModerationProviderMetadata(
            provider: ModerationProvider::TEXT,
            model: (string) config('moderation.openrouter.text_model'),
            promptVersion: (string) config('moderation.audit.prompt_version'),
            policyVersion: (string) config('moderation.audit.policy_version'),
        );
    }
}
