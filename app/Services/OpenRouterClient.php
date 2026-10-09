<?php

namespace App\Services;

use App\Exceptions\AiModerationProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;

/**
 * Единственный канал к ИИ: чат OpenRouter (https://openrouter.ai/docs), формат OpenAI.
 * Одним клиентом пользуются проверка текста и проверка фото; отличаются только модель и сообщения.
 */
class OpenRouterClient
{
    public function __construct(
        private readonly AiRequestThrottle $throttle,
    ) {}

    public function apiKey(): string
    {
        return trim((string) config('moderation.openrouter.api_key'));
    }

    public function url(string $path): string
    {
        return rtrim((string) config('moderation.openrouter.base_url'), '/').'/'.ltrim($path, '/');
    }

    /** Запрос с ключом и подписью приложения (OpenRouter показывает её в статистике). */
    public function pending(int $timeout): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withToken($this->apiKey())
            ->withHeaders([
                'HTTP-Referer' => (string) config('app.url'),
                'X-Title' => (string) config('app.name'),
            ])
            ->timeout($timeout);
    }

    /**
     * Отправляет сообщения модели и возвращает её JSON-ответ.
     *
     * @param  string  $scope  ключ учёта расхода запросов (часовой и суточный лимиты)
     * @param  array<int, array{role: string, content: string|array<int, array<string, mixed>>}>  $messages
     * @return array{data: array<string, mixed>, request_id: ?string}
     *
     * @throws AiModerationProviderException
     */
    public function completeJson(string $scope, string $model, array $messages, int $timeout): array
    {
        if ($this->apiKey() === '') {
            throw new AiModerationProviderException('Ключ OpenRouter не задан.');
        }

        if (trim($model) === '') {
            throw new AiModerationProviderException('Модель OpenRouter не указана.');
        }

        $response = $this->send($scope, [
            'model' => $model,
            'temperature' => 0,
            'max_tokens' => (int) config('moderation.http.max_output_tokens', 1200),
            'response_format' => ['type' => 'json_object'],
            'messages' => $messages,
        ], $timeout);

        if (! $response->successful()) {
            throw new AiModerationProviderException(
                $this->failureMessage($response),
                retryable: $this->isRetryableStatus($response->status()),
            );
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            // OpenRouter иногда отвечает HTTP 200, но кладёт ошибку модели/поставщика в тело ответа
            $error = $response->json('error');

            if (is_array($error)) {
                $code = (int) ($error['code'] ?? 0);

                throw new AiModerationProviderException(
                    'OpenRouter: '.$this->short((string) ($error['message'] ?? 'ошибка поставщика модели')),
                    retryable: $code === 0 || $this->isRetryableStatus($code),
                );
            }

            throw new AiModerationProviderException('OpenRouter вернул пустой ответ.', retryable: true);
        }

        try {
            $decoded = json_decode($this->stripCodeFence($content), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AiModerationProviderException(
                'OpenRouter вернул ответ не в формате JSON.',
                retryable: true,
                previous: $exception,
            );
        }

        if (! is_array($decoded)) {
            throw new AiModerationProviderException('OpenRouter вернул ответ неожиданной структуры.');
        }

        $requestId = $response->header('x-request-id') ?: $response->json('id');

        return [
            'data' => $decoded,
            'request_id' => is_string($requestId) && $requestId !== '' ? $requestId : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function send(string $scope, array $body, int $timeout): Response
    {
        $attempts = max(0, (int) config('moderation.http.retry_times')) + 1;
        $lastException = null;
        $response = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $this->throttle->forScope($scope)->wait();

            try {
                $response = $this->pending($timeout)->post($this->url('chat/completions'), $body);
                $lastException = null;
            } catch (ConnectionException $exception) {
                $lastException = $exception;

                continue;
            }

            if ($response->successful() || ! $this->isRetryableStatus($response->status())) {
                return $response;
            }

            if ($attempt < $attempts) {
                usleep(max(0, (int) config('moderation.http.retry_sleep_ms', 500)) * 1000);
            }
        }

        // последняя попытка не получила ответа вовсе (обрыв связи, таймаут)
        if ($lastException !== null || $response === null) {
            throw new AiModerationProviderException(
                'Не удалось соединиться с OpenRouter.',
                retryable: true,
                previous: $lastException,
            );
        }

        return $response;
    }

    /** Понятное объяснение ошибки HTTP для журнала проверок и уведомления администратору. */
    public function failureMessage(Response $response): string
    {
        $status = $response->status();
        $detail = $this->short((string) ($response->json('error.message') ?? $response->body()));

        return match ($status) {
            401 => 'OpenRouter отклонил ключ доступа (HTTP 401). Проверьте API-ключ.',
            402 => 'На счёте OpenRouter недостаточно средств (HTTP 402). Пополните баланс на openrouter.ai.',
            403 => 'OpenRouter отказал в запросе (HTTP 403)'.($detail !== '' ? ': '.$detail : '.'),
            404 => 'Модель не найдена или недоступна в OpenRouter (HTTP 404)'.($detail !== '' ? ': '.$detail : '.'),
            429 => 'OpenRouter ограничил частоту запросов (HTTP 429). Повторим позже.',
            default => 'OpenRouter вернул HTTP '.$status.($detail !== '' ? ': '.$detail : '.'),
        };
    }

    private function isRetryableStatus(int $status): bool
    {
        return $status === 408 || $status === 429 || $status >= 500;
    }

    private function short(string $text): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $text)), 0, 400);
    }

    private function stripCodeFence(string $content): string
    {
        $content = trim($content);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $content, $matches)) {
            return trim($matches[1]);
        }

        return $content;
    }
}
