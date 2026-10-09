<?php

namespace Tests\Feature;

use App\Services\ModerationConnectionTester;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ModerationConnectionTesterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('moderation.openrouter.api_key', 'sk-or-test');
        config()->set('moderation.openrouter.base_url', 'https://openrouter.test/api/v1');
        config()->set('moderation.openrouter.text_model', 'vendor/text-model');
        config()->set('moderation.openrouter.image_model', 'vendor/vision-model');
    }

    private function tester(): ModerationConnectionTester
    {
        return app(ModerationConnectionTester::class);
    }

    public function test_text_check_sends_a_tiny_request_to_the_text_model(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'ок']]]])]);

        $result = $this->tester()->text();

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('vendor/text-model', $result['message']);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://openrouter.test/api/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer sk-or-test')
            && $request['model'] === 'vendor/text-model'
            && $request['max_tokens'] === 16
            && is_string(data_get($request->data(), 'messages.0.content')));
    }

    public function test_image_check_sends_a_tiny_picture_to_the_image_model(): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'пиксель']]]])]);

        $result = $this->tester()->images();

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('принимает изображения', $result['message']);

        Http::assertSent(fn (Request $request): bool => $request['model'] === 'vendor/vision-model'
            && str_starts_with((string) data_get($request->data(), 'messages.0.content.1.image_url.url'), 'data:image/png;base64,'));
    }

    public function test_missing_key_is_reported_without_a_request(): void
    {
        config()->set('moderation.openrouter.api_key', '');
        Http::fake();

        $result = $this->tester()->text();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Ключ OpenRouter не задан', $result['message']);
        Http::assertNothingSent();
    }

    public function test_missing_model_is_reported_without_a_request(): void
    {
        config()->set('moderation.openrouter.image_model', '');
        Http::fake();

        $result = $this->tester()->images();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Модель не указана', $result['message']);
        Http::assertNothingSent();
    }

    public function test_rejected_key_is_explained_without_leaking_the_response_body(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'secret-internal-detail', 'code' => 401]], 401)]);

        $result = $this->tester()->text();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('отклонил ключ', $result['message']);
        $this->assertStringNotContainsString('secret-internal-detail', $result['message']);
    }

    public function test_empty_balance_is_explained(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Insufficient credits']], 402)]);

        $result = $this->tester()->text();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('недостаточно средств', $result['message']);
    }

    public function test_unknown_text_model_is_reported(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'No endpoints found for vendor/text-model']], 404)]);

        $result = $this->tester()->text();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Модель не найдена', $result['message']);
    }

    public function test_model_without_vision_is_reported_for_photos(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'No endpoints found that support image input']], 404)]);

        $result = $this->tester()->images();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('не умеет читать изображения', $result['message']);
        $this->assertStringContainsString('No endpoints found that support image input', $result['message']);
    }

    public function test_error_hidden_inside_http_200_is_not_treated_as_success(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Upstream overloaded', 'code' => 503]])]);

        $result = $this->tester()->text();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Upstream overloaded', $result['message']);
    }

    public function test_lost_connection_is_reported(): void
    {
        Http::fake(fn () => throw new ConnectionException('boom'));

        $result = $this->tester()->text();

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Не удалось соединиться', $result['message']);
    }

    public function test_check_does_not_spend_the_request_budget(): void
    {
        config()->set('moderation.budget.hourly_requests', 1);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'ок']]]])]);

        $this->assertTrue($this->tester()->text()['ok']);
        $this->assertTrue($this->tester()->text()['ok']);
    }
}
