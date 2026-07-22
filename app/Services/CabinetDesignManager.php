<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

class CabinetDesignManager
{
    private ?string $active = null;

    /**
     * @return array<string, string>
     */
    public function themes(): array
    {
        return config('cabinet_design.themes', []);
    }

    public function default(): string
    {
        $configured = (string) config('cabinet_design.default', 'legacy');

        if ($this->exists($configured)) {
            return $configured;
        }

        return (string) array_key_first($this->themes());
    }

    public function current(): string
    {
        if ($this->active !== null) {
            return $this->active;
        }

        $cached = Cache::get(config('cabinet_design.cache_key'));

        if (is_string($cached) && $this->exists($cached)) {
            return $this->active = $cached;
        }

        try {
            if (! Schema::hasTable('site_settings')) {
                return $this->active = $this->default();
            }

            $stored = SiteSetting::query()
                ->where('key', config('cabinet_design.setting_key'))
                ->value('value');
        } catch (Throwable) {
            return $this->active = $this->default();
        }

        $theme = is_string($stored) && $this->exists($stored)
            ? $stored
            : $this->default();

        Cache::forever(config('cabinet_design.cache_key'), $theme);

        return $this->active = $theme;
    }

    public function setActive(string $theme): void
    {
        if (! $this->exists($theme)) {
            throw new InvalidArgumentException("Unknown cabinet design [{$theme}].");
        }

        SiteSetting::query()->updateOrCreate(
            ['key' => config('cabinet_design.setting_key')],
            ['value' => $theme],
        );

        Cache::forever(config('cabinet_design.cache_key'), $theme);
        $this->active = $theme;
    }

    public function exists(string $theme): bool
    {
        return array_key_exists($theme, $this->themes());
    }

    public function viewPath(string $theme): string
    {
        return resource_path("views/themes/{$theme}");
    }
}
