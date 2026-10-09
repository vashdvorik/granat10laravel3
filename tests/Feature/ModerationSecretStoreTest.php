<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\ModerationSecretStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModerationSecretStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_is_encrypted_and_applied_to_runtime_config(): void
    {
        $store = app(ModerationSecretStore::class);
        $store->put('sk-or-secret');

        $stored = SiteSetting::query()->where('key', 'moderation.secret.openrouter')->value('value');
        $this->assertNotSame('sk-or-secret', $stored);
        $this->assertSame('sk-or-secret', $store->get());
        $this->assertTrue($store->has());

        config()->set('moderation.openrouter.api_key', null);
        $store->applyToConfig();
        $this->assertSame('sk-or-secret', config('moderation.openrouter.api_key'));
    }

    public function test_secret_is_trimmed_and_replaced_on_second_save(): void
    {
        $store = app(ModerationSecretStore::class);
        $store->put("  first-key \n");
        $store->put('second-key');

        $this->assertSame('second-key', $store->get());
        $this->assertSame(1, SiteSetting::query()->where('key', 'moderation.secret.openrouter')->count());
    }

    public function test_without_a_stored_secret_the_env_key_stays_in_use(): void
    {
        config()->set('moderation.openrouter.api_key', 'from-env');

        $store = app(ModerationSecretStore::class);
        $store->applyToConfig();

        $this->assertFalse($store->has());
        $this->assertNull($store->get());
        $this->assertSame('from-env', config('moderation.openrouter.api_key'));
    }

    public function test_corrupted_secret_is_ignored_instead_of_breaking_the_site(): void
    {
        SiteSetting::query()->create(['key' => 'moderation.secret.openrouter', 'value' => 'not-encrypted-garbage']);

        $this->assertNull(app(ModerationSecretStore::class)->get());
    }

    public function test_migration_removes_keys_of_the_retired_providers_only(): void
    {
        SiteSetting::query()->create(['key' => 'moderation.secret.text.google', 'value' => 'x']);
        SiteSetting::query()->create(['key' => 'moderation.secret.text.deepseek', 'value' => 'x']);
        SiteSetting::query()->create(['key' => 'moderation.secret.image.gemini', 'value' => 'x']);
        SiteSetting::query()->create(['key' => 'moderation.secret.image.openai_moderation', 'value' => 'x']);
        SiteSetting::query()->create(['key' => 'moderation.secret.openrouter', 'value' => 'keep']);
        SiteSetting::query()->create(['key' => 'site.name', 'value' => 'keep']);

        $migration = require database_path('migrations/2026_10_09_000004_remove_legacy_moderation_provider_secrets.php');
        $migration->up();

        $this->assertSame(
            ['moderation.secret.openrouter', 'site.name'],
            DB::table('site_settings')->orderBy('key')->pluck('key')->all(),
        );
    }
}
