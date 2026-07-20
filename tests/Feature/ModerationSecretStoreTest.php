<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\ModerationSecretStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationSecretStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_is_encrypted_and_applied_to_runtime_config(): void
    {
        $store = app(ModerationSecretStore::class);
        $store->put('text.openai_compatible', 'deepseek-secret');

        $stored = SiteSetting::query()->where('key', 'moderation.secret.text.openai_compatible')->value('value');
        $this->assertNotSame('deepseek-secret', $stored);
        $this->assertSame('deepseek-secret', $store->get('text.openai_compatible'));

        config()->set('moderation.openai_compatible.api_key', null);
        $store->applyToConfig();
        $this->assertSame('deepseek-secret', config('moderation.openai_compatible.api_key'));
    }
}
