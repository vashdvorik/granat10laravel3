<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * Проверка связи с OpenRouter по сохранённым настройкам: тот же ключ, та же модель, минимальный запрос.
 * Для модели фото в запрос кладётся крошечная картинка, чтобы убедиться, что модель умеет читать изображения.
 */
class ModerationConnectionTester
{
    /** Прозрачный PNG 1×1. */
    private const TEST_IMAGE = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    public function __construct(private readonly OpenRouterClient $openRouter) {}

    /** @return array{ok: bool, message: string} */
    public function text(): array
    {
        return $this->probe((string) config('moderation.openrouter.text_model'), withImage: false);
    }

    /** @return array{ok: bool, message: string} */
    public function images(): array
    {
        return $this->probe((string) config('moderation.openrouter.image_model'), withImage: true);
    }

    /** @return array{ok: bool, message: string} */
    private function probe(string $model, bool $withImage): array
    {
        if ($this->openRouter->apiKey() === '') {
            return ['ok' => false, 'message' => 'Ключ OpenRouter не задан. Введите его и нажмите «Сохранить».'];
        }

        if (trim($model) === '') {
            return ['ok' => false, 'message' => 'Модель не указана. Укажите её и нажмите «Сохранить».'];
        }

        try {
            $response = $this->openRouter->pending(25)->post($this->openRouter->url('chat/completions'), [
                'model' => $model,
                'max_tokens' => 16,
                'messages' => [[
                    'role' => 'user',
                    'content' => $withImage
                        ? [
                            ['type' => 'text', 'text' => 'Ответь одним словом: что на картинке?'],
                            ['type' => 'image_url', 'image_url' => ['url' => 'data:image/png;base64,'.self::TEST_IMAGE]],
                        ]
                        : 'Ответь одним словом: ок.',
                ]],
            ]);
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Не удалось соединиться с OpenRouter. Проверьте интернет на сервере.'];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => mb_substr($exception->getMessage(), 0, 300)];
        }

        return $this->result($response, $model, $withImage);
    }

    /** @return array{ok: bool, message: string} */
    private function result(Response $response, string $model, bool $withImage): array
    {
        if ($response->successful() && ! is_array($response->json('error'))) {
            return ['ok' => true, 'message' => "Связь есть: ключ принят, модель {$model} отвечает".($withImage ? ' и принимает изображения.' : '.')];
        }

        $status = $response->successful() ? (int) $response->json('error.code') : $response->status();
        $detail = (string) ($response->json('error.message') ?? '');

        if ($withImage && in_array($status, [400, 404], true)) {
            return ['ok' => false, 'message' => "Модель {$model} недоступна или не умеет читать изображения. Выберите модель с поддержкой фото."
                .($detail !== '' ? ' Ответ OpenRouter: '.mb_substr($detail, 0, 200) : '')];
        }

        if ($response->successful()) {
            // HTTP 200, но ошибка модели или поставщика лежит в теле ответа
            return ['ok' => false, 'message' => 'OpenRouter: '.($detail !== '' ? mb_substr($detail, 0, 250) : 'ошибка поставщика модели.')];
        }

        return ['ok' => false, 'message' => $this->openRouter->failureMessage($response)];
    }
}
