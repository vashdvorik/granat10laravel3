<?php

namespace Tests\Unit;

use App\Services\ModerationProviderMetadataResolver;
use Tests\TestCase;

class ModerationProviderMetadataResolverTest extends TestCase
{
    public function test_google_metadata_uses_configured_model_and_versions(): void
    {
        config()->set('moderation.provider', 'google');
        config()->set('moderation.google.model', 'gemini-test');
        config()->set('moderation.audit.prompt_version', 'prompt-v3');
        config()->set('moderation.audit.policy_version', 'policy-v2');

        $metadata = app(ModerationProviderMetadataResolver::class)->resolve();

        $this->assertSame('google', $metadata->provider);
        $this->assertSame('gemini-test', $metadata->model);
        $this->assertSame('prompt-v3', $metadata->promptVersion);
        $this->assertSame('policy-v2', $metadata->policyVersion);
    }

    public function test_openai_compatible_metadata_uses_its_model(): void
    {
        config()->set('moderation.provider', 'openai_compatible');
        config()->set('moderation.openai_compatible.model', 'vendor/model');

        $metadata = app(ModerationProviderMetadataResolver::class)->resolve();

        $this->assertSame('vendor/model', $metadata->model);
    }
}
