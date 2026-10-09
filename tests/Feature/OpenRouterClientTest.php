<?php

namespace Tests\Feature;

use App\Exceptions\AiModerationBudgetExceededException;
use App\Exceptions\AiModerationProviderException;
use App\Services\OpenRouterClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Единственный канал к ИИ: чат OpenRouter. */
class OpenRouterClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('moderation.openrouter.api_key', 'sk-or-test');
        config()->set('moderation.openrouter.base_url', 'https://openrouter.test/api/v1');
        config()->set('moderation.http.requests_per_minute', 0);
        config()->set('moderation.http.retry_times', 0);
        config()->set('moderation.http.retry_sleep_ms', 0);
        config()->set('moderation.http.max_output_tokens', 777);
        config()->set('app.url', 'https://shop.test');
        config()->set('app.name', 'BIMM');
    }

    private function client(): OpenRouterClient
    {
        return app(OpenRouterClient::class);
    }

    /** @return array<int, array{role: string, content: string}> */
    private function messages(): array
    {
        return [['role' => 'user', 'content' => 'Привет']];
    }

    private function answer(string $content, array $extra = []): array
    {
        return $extra + ['id' => 'gen-123', 'choices' => [['message' => ['content' => $content]]]];
    }

    private function expectFailure(callable $call, string $message, bool $retryable): void
    {
        try {
            $call();
            $this->fail('Ожидалась ошибка OpenRouter.');
        } catch (AiModerationProviderException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
            $this->assertSame($retryable, $exception->isRetryable());
        }
    }

    public function test_sends_chat_request_with_key_model_and_json_mode_and_returns_answer(): void
    {
        Http::fake(['*' => Http::response($this->answer('{"decision":"allow"}'))]);

        $answer = $this->client()->completeJson('text:openrouter', 'openai/gpt-4o-mini', $this->messages(), 30);

        $this->assertSame(['decision' => 'allow'], $answer['data']);
        $this->assertSame('gen-123', $answer['request_id']);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://openrouter.test/api/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer sk-or-test')
            && $request->hasHeader('HTTP-Referer', 'https://shop.test')
            && $request->hasHeader('X-Title', 'BIMM')
            && $request['model'] === 'openai/gpt-4o-mini'
            && $request['temperature'] === 0
            && $request['max_tokens'] === 777
            && data_get($request->data(), 'response_format.type') === 'json_object'
            && data_get($request->data(), 'messages.0.content') === 'Привет');
    }

    public function test_code_fence_around_json_is_stripped(): void
    {
        Http::fake(['*' => Http::response($this->answer("```json\n{\"decision\":\"review\"}\n```"))]);

        $answer = $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30);

        $this->assertSame('review', $answer['data']['decision']);
    }

    public function test_missing_key_fails_without_any_request(): void
    {
        config()->set('moderation.openrouter.api_key', '  ');
        Http::fake();

        $this->expectFailure(
            fn () => $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30),
            'Ключ OpenRouter не задан',
            retryable: false,
        );

        Http::assertNothingSent();
    }

    public function test_missing_model_fails_without_any_request(): void
    {
        Http::fake();

        $this->expectFailure(
            fn () => $this->client()->completeJson('text:openrouter', ' ', $this->messages(), 30),
            'Модель OpenRouter не указана',
            retryable: false,
        );

        Http::assertNothingSent();
    }

    /** @return array<string, array{0: int, 1: string, 2: bool}> */
    public static function failures(): array
    {
        return [
            'bad key' => [401, 'отклонил ключ', false],
            'no credits' => [402, 'недостаточно средств', false],
            'refused' => [403, 'отказал в запросе', false],
            'unknown model' => [404, 'Модель не найдена', false],
            'bad request' => [400, 'HTTP 400', false],
            'rate limit' => [429, 'ограничил частоту', true],
            'timeout' => [408, 'HTTP 408', true],
            'server error' => [502, 'HTTP 502', true],
        ];
    }

    /** @dataProvider failures */
    public function test_http_errors_are_explained_and_classified(int $status, string $message, bool $retryable): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'upstream said no', 'code' => $status]], $status)]);

        $this->expectFailure(
            fn () => $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30),
            $message,
            $retryable,
        );
    }

    public function test_error_text_from_openrouter_is_included_for_unclassified_statuses(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Provider returned error']], 400)]);

        $this->expectFailure(
            fn () => $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30),
            'HTTP 400: Provider returned error',
            retryable: false,
        );
    }

    public function test_error_inside_http_200_is_reported_and_retried_for_server_codes(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Upstream overloaded', 'code' => 503]])]);

        $this->expectFailure(
            fn () => $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30),
            'OpenRouter: Upstream overloaded',
            retryable: true,
        );
    }

    public function test_client_error_inside_http_200_is_not_retried(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Invalid model', 'code' => 400]])]);

        $this->expectFailure(
            fn () => $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30),
            'Invalid model',
            retryable: false,
        );
    }

    public function test_empty_answer_is_retryable(): void
    {
        Http::fake(['*' => Http::response($this->answer('   '))]);

        $this->expectFailure(
            fn () => $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30),
            'пустой ответ',
            retryable: true,
        );
    }

    public function test_answer_that_is_not_json_is_retryable(): void
    {
        Http::fake(['*' => Http::response($this->answer('Конечно! Вот ответ: всё хорошо.'))]);

        $this->expectFailure(
            fn () => $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30),
            'не в формате JSON',
            retryable: true,
        );
    }

    public function test_json_that_is_not_an_object_is_rejected(): void
    {
        Http::fake(['*' => Http::response($this->answer('"allow"'))]);

        $this->expectFailure(
            fn () => $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30),
            'неожиданной структуры',
            retryable: false,
        );
    }

    public function test_temporary_failures_are_retried_the_configured_number_of_times(): void
    {
        config()->set('moderation.http.retry_times', 2);
        Http::fake(['*' => Http::sequence()
            ->push(['error' => ['message' => 'busy']], 503)
            ->push(['error' => ['message' => 'busy']], 429)
            ->push($this->answer('{"decision":"allow"}'))]);

        $answer = $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30);

        $this->assertSame('allow', $answer['data']['decision']);
        Http::assertSentCount(3);
    }

    public function test_permanent_failures_are_not_retried(): void
    {
        config()->set('moderation.http.retry_times', 3);
        Http::fake(['*' => Http::response(['error' => ['message' => 'bad key']], 401)]);

        try {
            $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30);
        } catch (AiModerationProviderException) {
        }

        Http::assertSentCount(1);
    }

    public function test_lost_connection_is_retryable_and_retried(): void
    {
        config()->set('moderation.http.retry_times', 1);
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->expectFailure(
            fn () => $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30),
            'Не удалось соединиться с OpenRouter',
            retryable: true,
        );
    }

    public function test_every_request_spends_the_hourly_and_daily_budget(): void
    {
        config()->set('moderation.budget.hourly_requests', 2);
        config()->set('moderation.budget.daily_requests', 100);
        Http::fake(['*' => Http::response($this->answer('{"decision":"allow"}'))]);

        $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30);
        $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30);

        $this->expectException(AiModerationBudgetExceededException::class);
        $this->client()->completeJson('text:openrouter', 'a/b', $this->messages(), 30);
    }
}
