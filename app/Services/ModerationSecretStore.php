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
        'image.gemini' => 'moderation.images.api_key',
        'image.google' => 'moderation.images.api_key',
        'image.openai_compatible' => 'moderation.images.api_key',
        'image.openai_moderation' => 'moderation.images.api_key',
    ];

    private const ENV_KEYS = [
        'text.google' => ['AI_TEXT_GOOGLE_API_KEY', 'AI_MODERATION_API_KEY', 'GEMINI_API_KEY'],
        'text.openai_compatible' => ['AI_TEXT_OPENAI_COMPATIBLE_API_KEY', 'AI_MODERATION_API_KEY'],
        'text.deepseek' => ['AI_TEXT_DEEPSEEK_API_KEY'],
        'image.gemini' => ['AI_IMAGE_GEMINI_API_KEY', 'AI_IMAGE_GOOGLE_API_KEY', 'AI_IMAGE_MODERATION_API_KEY'],
        'image.google' => ['AI_IMAGE_GEMINI_API_KEY', 'AI_IMAGE_GOOGLE_API_KEY', 'AI_IMAGE_MODERATION_API_KEY'],
        'image.openai_compatible' => ['AI_IMAGE_OPENAI_COMPATIBLE_API_KEY', 'AI_IMAGE_OPENAI_API_KEY', 'AI_IMAGE_MODERATION_API_KEY'],
        'image.openai_moderation' => ['AI_IMAGE_OPENAI_MODERATION_API_KEY', 'AI_IMAGE_OPENAI_API_KEY', 'AI_IMAGE_MODERATION_API_KEY'],
    ];

    public function applyToConfig(): void
    {
        foreach ([
            'moderation.google.api_key' => $this->get('text.google'),
            'moderation.openai_compatible.api_key' => $this->get('text.openai_compatible'),
            'moderation.deepseek.api_key' => $this->get('text.deepseek'),
        ] as $configKey => $secret) {
            if ($secret !== null) {
                config()->set($configKey, $secret);
            }
        }

        $activeImageSecret = $this->activeImageSecretName((string) config('moderation.images.provider', 'gemini'));
        $imageSecret = $this->get($activeImageSecret);

        if ($imageSecret !== null) {
            config()->set('moderation.images.api_key', $imageSecret);
        }
    }

    public function get(string $name): ?string
    {
        if (! $this->available()) {
            return null;
        }

        try {
            foreach ($this->nameVariants($name) as $variant) {
                $encrypted = SiteSetting::query()->where('key', self::PREFIX.$variant)->value('value');

                if (! is_string($encrypted) || $encrypted === '') {
                    continue;
                }

                $value = Crypt::decryptString($encrypted);

                return $value === '' ? null : $value;
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    public function has(string $name): bool
    {
        return $this->get($name) !== null;
    }

    public function put(string $name, string $secret): void
    {
        $name = $this->normalizeImageName($name);
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
        $activeImage = $this->activeImageSecretName((string) ($values['AI_IMAGE_MODERATION_PROVIDER'] ?? 'gemini'));

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

    /**
     * @return array<int, string>
     */
    private function nameVariants(string $name): array
    {
        return match ($this->normalizeImageName($name)) {
            'image.gemini' => ['image.gemini', 'image.google'],
            'image.openai_compatible' => ['image.openai_compatible'],
            'image.openai_moderation' => ['image.openai_moderation'],
            default => [$name],
        };
    }

    private function normalizeImageName(string $name): string
    {
        return match ($name) {
            'image.google' => 'image.gemini',
            default => $name,
        };
    }

    private function activeImageSecretName(string $provider): string
    {
        return match ($provider) {
            'google', 'gemini' => 'image.gemini',
            'openai', 'openai_compatible' => 'image.openai_compatible',
            'openai_moderation' => 'image.openai_moderation',
            default => 'image.openai_compatible',
        };
    }
}
