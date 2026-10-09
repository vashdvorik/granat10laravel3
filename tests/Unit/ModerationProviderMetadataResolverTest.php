<?php

namespace Tests\Unit;

use App\Services\ModerationProviderMetadataResolver;
use Tests\TestCase;

class ModerationProviderMetadataResolverTest extends TestCase
{
    public function test_metadata_names_openrouter_with_configured_text_model_and_versions(): void
    {
        config()->set('moderation.openrouter.text_model', 'vendor/text-model');
        config()->set('moderation.audit.prompt_version', 'prompt-v3');
        config()->set('moderation.audit.policy_version', 'policy-v2');

        $metadata = app(ModerationProviderMetadataResolver::class)->resolve();

        $this->assertSame('openrouter', $metadata->provider);
        $this->assertSame('vendor/text-model', $metadata->model);
        $this->assertSame('prompt-v3', $metadata->promptVersion);
        $this->assertSame('policy-v2', $metadata->policyVersion);
    }
}
