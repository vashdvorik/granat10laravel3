<?php

namespace Tests\Feature;

use App\Services\ModerationConnectionTester;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ModerationConnectionTesterTest extends TestCase
{
    public function test_text_google_connection_uses_model_endpoint(): void
    {
        config()->set('moderation.provider', 'google');
        config()->set('moderation.google.api_key', 'secret');
        config()->set('moderation.google.model', 'gemini-test');
        config()->set('moderation.google.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        Http::fake(['*' => Http::response(['name' => 'gemini-test'], 200)]);

        $result = app(ModerationConnectionTester::class)->text();

        $this->assertTrue($result['ok']);
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/models/gemini-test'));
    }

    public function test_openai_image_connection_reports_authentication_failure_without_leaking_body(): void
    {
        config()->set('moderation.images.provider', 'openai_moderation');
        config()->set('moderation.images.openai_moderation.api_key', 'bad-secret');
        config()->set('moderation.images.openai_moderation.base_url', 'https://api.openai.com/v1');
        Http::fake(['*' => Http::response(['error' => ['message' => 'sensitive provider body']], 401)]);

        $result = app(ModerationConnectionTester::class)->images();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('HTTP 401', $result['message']);
        $this->assertStringNotContainsString('sensitive provider body', $result['message']);
    }

    public function test_openai_image_connection_reports_rate_limit_separately(): void
    {
        config()->set('moderation.images.provider', 'openai_moderation');
        config()->set('moderation.images.openai_moderation.api_key', 'openai-secret');
        config()->set('moderation.images.openai_moderation.base_url', 'https://api.openai.com/v1');
        Http::fake(['*' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        $result = app(ModerationConnectionTester::class)->images();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('HTTP 429', $result['message']);
        $this->assertStringContainsString('rate limit', $result['message']);
        $this->assertStringNotContainsString('Проверьте ключ, URL и модель', $result['message']);
    }

    public function test_deepseek_connection_uses_official_models_endpoint(): void
    {
        config()->set('moderation.provider', 'deepseek');
        config()->set('moderation.deepseek.api_key', 'deepseek-secret');
        config()->set('moderation.deepseek.base_url', 'https://api.deepseek.com');
        Http::fake(['*' => Http::response(['object' => 'list'], 200)]);

        $result = app(ModerationConnectionTester::class)->text();

        $this->assertTrue($result['ok']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.deepseek.com/models');
    }
}
