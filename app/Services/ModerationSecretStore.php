<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ModerationSecretStore
{
    private const PREFIX = 'moderation.secret.';

    private const CONFIG_KEYS = [
        'text.google' => 'moderation.google.api_key',
        'text.openai_compatible' => 'moderation.openai_compatible.api_key',
        'text.deepseek' => 'moderation.deepseek.api_key',
        'image.google' => 'moderation.images.google.api_key',
        'image.openai_moderation' => 'moderation.images.openai_moderation.api_key',
    ];

    private const ENV_KEYS = [
        'text.google' => ['AI_TEXT_GOOGLE_API_KEY', 'AI_MODERATION_API_KEY', 'GEMINI_API_KEY'],
        'text.openai_compatible' => ['AI_TEXT_OPENAI_COMPATIBLE_API_KEY', 'AI_MODERATION_API_KEY'],
        'text.deepseek' => ['AI_TEXT_DEEPSEEK_API_KEY'],
        'image.google' => ['AI_IMAGE_GOOGLE_API_KEY', 'AI_IMAGE_MODERATION_API_KEY'],
        'image.openai_moderation' => ['AI_IMAGE_OPENAI_API_KEY', 'AI_IMAGE_MODERATION_API_KEY'],
    ];

    public function applyToConfig(): void
    {
        foreach (self::CONFIG_KEYS as $name => $configKey) {
            $secret = $this->get($name);

            if ($secret !== null) {
                config()->set($configKey, $secret);
            }
        }
    }

    public function get(string $name): ?string
    {
        if (! $this->available()) {
            return null;
        }

        try {
            $encrypted = SiteSetting::query()->where('key', self::PREFIX.$name)->value('value');

            if (! is_string($encrypted) || $encrypted === '') {
                return null;
            }

            $value = Crypt::decryptString($encrypted);

            return $value === '' ? null : $value;
        } catch (Throwable) {
            return null;
        }
    }

    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    public function put(string $name, string $secret): void
    {
        $this->ensureKnown($name);

        SiteSetting::query()->updateOrCreate(
            ['key' => self::PREFIX.$name],
            ['value' => Crypt::encryptString($secret)],
        );

        config()->set(self::CONFIG_KEYS[$name], $secret);
    }

    public function importLegacy(EnvironmentSettingsService $environment): int
    {
        $values = $environment->values();
        $imported = 0;
        $configuredTextProvider = $values['PRODUCT_MODERATION_AI_PROVIDER'] ?? 'google';
        if ($configuredTextProvider === 'google' && (
            str_starts_with((string) ($values['AI_MODERATION_MODEL'] ?? ''), 'deepseek-')
            || str_contains((string) ($values['AI_MODERATION_BASE_URL'] ?? ''), 'deepseek.com')
        )) {
            $configuredTextProvider = 'deepseek';
        }

        $activeText = match ($configuredTextProvider) {
            'google' => 'text.google',
            'deepseek' => 'text.deepseek',
            default => 'text.openai_compatible',
        };
        $activeImage = ($values['AI_IMAGE_MODERATION_PROVIDER'] ?? 'google') === 'google'
            ? 'image.google'
            : 'image.openai_moderation';

        foreach (self::ENV_KEYS as $name => $keys) {
            if ($this->has($name)) {
                continue;
            }

            foreach ($keys as $key) {
                if ($key === 'AI_MODERATION_API_KEY' && $name !== $activeText) {
                    continue;
                }

                if ($key === 'AI_IMAGE_MODERATION_API_KEY' && $name !== $activeImage) {
                    continue;
                }

                $value = $values[$key] ?? null;

                if (is_string($value) && $value !== '') {
                    $this->put($name, $value);
                    $imported++;
                    break;
                }
            }
        }

        return $imported;
    }

    private function available(): bool
    {
        try {
            return Schema::hasTable('site_settings');
        } catch (Throwable) {
            return false;
        }
    }

    private function ensureKnown(string $name): void
    {
        if (! array_key_exists($name, self::CONFIG_KEYS)) {
            throw new \InvalidArgumentException("Unknown moderation secret [{$name}].");
        }
    }
}
