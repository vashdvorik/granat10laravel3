<?php

namespace App\Services;

use RuntimeException;

class EnvironmentSettingsService
{
    private const ALLOWED_KEYS = [
        'QUEUE_CONNECTION',
        'PRODUCT_MODERATION_AI_ENABLED',
        'PRODUCT_MODERATION_AI_PROVIDER',
        'PRODUCT_MODERATION_AI_MODE',
        'PRODUCT_MODERATION_QUEUE',
        'PRODUCT_MODERATION_TIMEOUT',
        'PRODUCT_MODERATION_MAX_ATTEMPTS',
        'PRODUCT_MODERATION_FAKE_DECISION',
        'PRODUCT_MODERATION_AUTO_APPROVE_MAX_RISK',
        'PRODUCT_MODERATION_AUTO_APPROVE_EXCLUDED_CATEGORIES',
        'AI_MODERATION_API_KEY',
        'AI_MODERATION_MODEL',
        'AI_MODERATION_BASE_URL',
        'AI_MODERATION_CHAT_COMPLETIONS_PATH',
        'AI_MODERATION_MAX_OUTPUT_TOKENS',
        'AI_MODERATION_RETRY_TIMES',
        'AI_MODERATION_RETRY_SLEEP_MS',
        'AI_MODERATION_REQUESTS_PER_MINUTE',
        'AI_MODERATION_RATE_LIMIT_SAFETY_SECONDS',
        'AI_MODERATION_HOURLY_REQUEST_LIMIT',
        'AI_MODERATION_DAILY_REQUEST_LIMIT',
        'AI_TEXT_GOOGLE_API_KEY',
        'AI_TEXT_GOOGLE_MODEL',
        'AI_TEXT_GOOGLE_BASE_URL',
        'AI_TEXT_OPENAI_COMPATIBLE_API_KEY',
        'AI_TEXT_OPENAI_COMPATIBLE_MODEL',
        'AI_TEXT_OPENAI_COMPATIBLE_BASE_URL',
        'AI_TEXT_OPENAI_COMPATIBLE_CHAT_COMPLETIONS_PATH',
        'AI_TEXT_DEEPSEEK_API_KEY',
        'AI_TEXT_DEEPSEEK_MODEL',
        'AI_TEXT_DEEPSEEK_BASE_URL',
        'AI_TEXT_DEEPSEEK_CHAT_COMPLETIONS_PATH',
        'AI_IMAGE_MODERATION_ENABLED',
        'AI_IMAGE_MODERATION_PROVIDER',
        'AI_IMAGE_MODERATION_QUEUE',
        'AI_IMAGE_MODERATION_TIMEOUT',
        'AI_IMAGE_MODERATION_MAX_ATTEMPTS',
        'AI_IMAGE_MODERATION_API_KEY',
        'AI_IMAGE_MODERATION_MODEL',
        'AI_IMAGE_MODERATION_BASE_URL',
        'AI_IMAGE_GOOGLE_API_KEY',
        'AI_IMAGE_GOOGLE_MODEL',
        'AI_IMAGE_GOOGLE_BASE_URL',
        'AI_IMAGE_OPENAI_API_KEY',
        'AI_IMAGE_OPENAI_MODEL',
        'AI_IMAGE_OPENAI_BASE_URL',
        'GEMINI_API_KEY',
        'GEMINI_MODEL',
        'GEMINI_BASE_URL',
        'GEMINI_MAX_OUTPUT_TOKENS',
        'GEMINI_RETRY_TIMES',
        'GEMINI_RETRY_SLEEP_MS',
        'GEMINI_REQUESTS_PER_MINUTE',
        'GEMINI_RATE_LIMIT_SAFETY_SECONDS',
    ];

    public function values(): array
    {
        $contents = $this->contents();
        $values = [];

        foreach (self::ALLOWED_KEYS as $key) {
            $values[$key] = $this->extract($contents, $key);
        }

        return $values;
    }

    public function hasValue(string $key): bool
    {
        $this->ensureAllowed($key);

        return filled($this->extract($this->contents(), $key));
    }

    public function update(array $values): void
    {
        $contents = $this->contents();

        foreach ($values as $key => $value) {
            $this->ensureAllowed($key);
            $contents = $this->replace($contents, $key, $this->encode($value));
        }

        $path = $this->path();
        $temporaryPath = $path.'.tmp';

        if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Не удалось записать временный файл настроек.');
        }

        if (! rename($temporaryPath, $path)) {
            @unlink($temporaryPath);

            throw new RuntimeException('Не удалось заменить файл .env.');
        }
    }

    private function contents(): string
    {
        $path = $this->path();

        if (! is_file($path)) {
            throw new RuntimeException("Файл настроек не найден: {$path}");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('Не удалось прочитать файл .env.');
        }

        return $contents;
    }

    private function path(): string
    {
        return (string) config('environment-settings.path', base_path('.env'));
    }

    private function ensureAllowed(string $key): void
    {
        if (! in_array($key, self::ALLOWED_KEYS, true)) {
            throw new RuntimeException("Переменная {$key} недоступна для изменения.");
        }
    }

    private function extract(string $contents, string $key): ?string
    {
        if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches)) {
            return null;
        }

        $value = trim($matches[1]);

        if (strlen($value) >= 2 && $value[0] === '"' && $value[-1] === '"') {
            return stripcslashes(substr($value, 1, -1));
        }

        return $value;
    }

    private function replace(string $contents, string $key, string $value): string
    {
        $line = "{$key}={$value}";
        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents)) {
            return (string) preg_replace($pattern, $line, $contents, 1);
        }

        return rtrim($contents).PHP_EOL.$line.PHP_EOL;
    }

    private function encode(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z0-9_.:\/,-]+$/', $value)) {
            return $value;
        }

        return '"'.addcslashes($value, '\\"').'"';
    }
}
