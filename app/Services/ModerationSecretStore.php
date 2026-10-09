<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Ключ OpenRouter, введённый в админке: хранится в базе в зашифрованном виде (Laravel Crypt)
 * и подменяет `OPENROUTER_API_KEY` из .env. Один ключ служит и для текста, и для фото.
 */
class ModerationSecretStore
{
    public const SETTING_KEY = 'moderation.secret.openrouter';

    private const CONFIG_KEY = 'moderation.openrouter.api_key';

    public function applyToConfig(): void
    {
        $secret = $this->get();

        if ($secret !== null) {
            config()->set(self::CONFIG_KEY, $secret);
        }
    }

    public function get(): ?string
    {
        if (! $this->available()) {
            return null;
        }

        try {
            $encrypted = SiteSetting::query()->where('key', self::SETTING_KEY)->value('value');

            if (! is_string($encrypted) || $encrypted === '') {
                return null;
            }

            $value = Crypt::decryptString($encrypted);

            return $value === '' ? null : $value;
        } catch (Throwable) {
            return null;
        }
    }

    public function has(): bool
    {
        return $this->get() !== null;
    }

    public function put(string $secret): void
    {
        SiteSetting::query()->updateOrCreate(
            ['key' => self::SETTING_KEY],
            ['value' => Crypt::encryptString(trim($secret))],
        );

        config()->set(self::CONFIG_KEY, trim($secret));
    }

    private function available(): bool
    {
        try {
            return app(\App\Services\SchemaInspector::class)->hasTable('site_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
