<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class ModerationConnectionTester
{
    public function __construct(private readonly AiProviderUrlValidator $urlValidator) {}

    /** @return array{ok: bool, message: string} */
    public function text(): array
    {
        $provider = (string) config('moderation.provider');

        return match ($provider) {
            'google' => $this->google(
                (string) config('moderation.google.base_url'),
                (string) config('moderation.google.model'),
                (string) config('moderation.google.api_key'),
            ),
            'openai_compatible' => $this->openAiCompatible(
                (string) config('moderation.openai_compatible.base_url'),
                (string) config('moderation.openai_compatible.api_key'),
            ),
            'deepseek' => $this->openAiCompatible(
                (string) config('moderation.deepseek.base_url'),
                (string) config('moderation.deepseek.api_key'),
            ),
            default => ['ok' => false, 'message' => 'Выбранный текстовый провайдер не поддерживает проверку соединения.'],
        };
    }

    /** @return array{ok: bool, message: string} */
    public function images(): array
    {
        $provider = $this->normalizeImageProvider((string) config('moderation.images.provider', 'gemini'));
        $baseUrl = rtrim((string) config('moderation.images.base_url'), '/');
        $apiKey = (string) config('moderation.images.api_key');

        return match ($provider) {
            'gemini' => $this->request(function () use ($baseUrl, $apiKey) {
                $this->validate($baseUrl, $apiKey);

                return $this->imageRequest('gemini', $apiKey)
                    ->timeout(15)
                    ->get($baseUrl.'/models/'.(string) config('moderation.images.model'));
            }),
            'openai_moderation' => $this->request(function () use ($baseUrl, $apiKey) {
                $this->validate($baseUrl, $apiKey);

                return $this->imageRequest('openai_moderation', $apiKey)->timeout(15)
                    ->post($baseUrl.'/moderations', [
                        'model' => (string) config('moderation.images.model'),
                        'input' => 'Connection test.',
                    ]);
            }, fn (Response $response): string => $this->openAiModerationMessage($response)),
            default => $this->request(function () use ($baseUrl, $apiKey) {
                $this->validate($baseUrl, $apiKey);

                return $this->imageRequest('openai_compatible', $apiKey)->timeout(15)->get($baseUrl.'/models');
            }),
        };
    }

    /** @return array{ok: bool, message: string} */
    private function google(string $baseUrl, string $model, string $apiKey): array
    {
        $baseUrl = rtrim($baseUrl, '/');

        return $this->request(function () use ($baseUrl, $model, $apiKey) {
            $this->validate($baseUrl, $apiKey);

            return Http::acceptJson()->withHeaders(['x-goog-api-key' => $apiKey])->timeout(15)
                ->get("{$baseUrl}/models/{$model}");
        });
    }

    /** @return array{ok: bool, message: string} */
    private function openAiCompatible(string $baseUrl, string $apiKey): array
    {
        $baseUrl = rtrim($baseUrl, '/');

        return $this->request(function () use ($baseUrl, $apiKey) {
            $this->validate($baseUrl, $apiKey);

            return Http::acceptJson()->withToken($apiKey)->timeout(15)->get($baseUrl.'/models');
        });
    }

    private function validate(string $baseUrl, string $apiKey): void
    {
        if ($apiKey === '') {
            throw new \RuntimeException('API-ключ выбранного провайдера не настроен.');
        }

        $this->urlValidator->validate($baseUrl);
    }

    /** @return array{ok: bool, message: string} */
    private function request(callable $callback, ?callable $errorMessage = null): array
    {
        try {
            $response = $callback();

            if ($response->successful()) {
                return ['ok' => true, 'message' => 'Соединение установлено, ключ и endpoint приняты.'];
            }

            return ['ok' => false, 'message' => $errorMessage
                ? $errorMessage($response)
                : 'Провайдер вернул HTTP '.$response->status().'. Проверьте ключ, URL и модель.'];
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Не удалось соединиться с API провайдера.'];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => mb_substr($exception->getMessage(), 0, 300)];
        }
    }

    private function openAiModerationMessage(Response $response): string
    {
        return match ($response->status()) {
            401 => 'OpenAI отклонил ключ доступа (HTTP 401). Проверьте API key для модерации изображений.',
            402 => 'OpenAI вернул HTTP 402. Проверьте биллинг и доступную квоту аккаунта.',
            429 => 'OpenAI вернул HTTP 429. Подключение к API есть, но сработал rate limit или исчерпана квота. Проверьте лимиты, биллинг и повторите попытку позже.',
            default => 'OpenAI moderation API вернул HTTP '.$response->status().'. Проверьте ключ, endpoint и доступность аккаунта.',
        };
    }

    private function normalizeImageProvider(string $provider): string
    {
        return match ($provider) {
            'google' => 'gemini',
            'openai' => 'openai_compatible',
            default => $provider,
        };
    }

    private function imageRequest(string $provider, string $apiKey)
    {
        $authHeader = (string) config('moderation.images.auth_header', $this->defaultImageAuthHeader($provider));
        $authPrefix = (string) config('moderation.images.auth_prefix', $this->defaultImageAuthPrefix($provider));
        $headerValue = $authHeader === 'x-goog-api-key'
            ? $apiKey
            : (trim($authPrefix) === '' ? $apiKey : rtrim($authPrefix).' '.$apiKey);

        return Http::acceptJson()
            ->asJson()
            ->withHeaders([$authHeader => $headerValue]);
    }

    private function defaultImageAuthHeader(string $provider): string
    {
        return $provider === 'gemini' ? 'x-goog-api-key' : 'Authorization';
    }

    private function defaultImageAuthPrefix(string $provider): string
    {
        return $provider === 'gemini' ? '' : 'Bearer';
    }
}
