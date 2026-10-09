<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CatalogBannerManager
{
    private bool $resolved = false;

    /**
     * @var array<int, array{image: string, link: ?string}>
     */
    private array $banners = [];

    /**
     * @return array<int, array{image: string, link: ?string}>
     */
    public function all(): array
    {
        if ($this->resolved) {
            return $this->banners;
        }

        $this->resolved = true;
        $cached = Cache::get(config('catalog_banner.cache_key'));

        if (is_array($cached)) {
            return $this->banners = $this->normalize($cached);
        }

        try {
            if (! app(\App\Services\SchemaInspector::class)->hasTable('site_settings')) {
                return [];
            }

            $setting = SiteSetting::query()
                ->where('key', config('catalog_banner.setting_key'))
                ->first();

            if ($setting !== null) {
                $decoded = json_decode((string) $setting->value, true);
                $banners = is_array($decoded) ? $this->normalize($decoded) : [];
            } else {
                $banners = $this->legacyBanners();
            }
        } catch (Throwable) {
            return [];
        }

        Cache::forever(config('catalog_banner.cache_key'), $banners);

        return $this->banners = $banners;
    }

    /**
     * @return array{url: string, link: ?string}
     */
    public function forDisplay(): array
    {
        $disk = (string) config('catalog_banner.disk', 'public');
        $available = array_values(array_filter(
            $this->all(),
            static fn (array $banner): bool => Storage::disk($disk)->exists($banner['image'])
        ));

        if ($available === []) {
            return [
                'url' => asset((string) config('catalog_banner.fallback', 'img/shop-banner.jpg')),
                'link' => null,
            ];
        }

        $selected = $available[$this->randomIndex(count($available) - 1)];

        return [
            'url' => Storage::disk($disk)->url($selected['image']),
            'link' => $selected['link'],
        ];
    }

    /**
     * @param  array<int, mixed>  $banners
     */
    public function setBanners(array $banners): void
    {
        $previousImages = array_column($this->all(), 'image');
        $banners = $this->normalize($banners);

        if ($banners === []) {
            SiteSetting::query()
                ->where('key', config('catalog_banner.setting_key'))
                ->delete();
        } else {
            SiteSetting::query()->updateOrCreate(
                ['key' => config('catalog_banner.setting_key')],
                ['value' => json_encode($banners, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)],
            );
        }

        SiteSetting::query()
            ->whereIn('key', [
                config('catalog_banner.legacy_image_setting_key'),
                config('catalog_banner.legacy_link_setting_key'),
            ])
            ->delete();

        Cache::forget(config('catalog_banner.cache_key'));
        Cache::forget(config('catalog_banner.legacy_image_cache_key'));
        Cache::forget(config('catalog_banner.legacy_link_cache_key'));
        $this->resolved = true;
        $this->banners = $banners;

        $currentImages = array_column($banners, 'image');
        $removedImages = array_diff($previousImages, $currentImages);
        $disk = (string) config('catalog_banner.disk', 'public');

        foreach ($removedImages as $removedImage) {
            if ($this->isManagedPath($removedImage)) {
                Storage::disk($disk)->delete($removedImage);
            }
        }
    }

    public function isAllowedLink(?string $link): bool
    {
        if (! is_string($link) || trim($link) === '') {
            return false;
        }

        $link = trim($link);

        if (Str::startsWith($link, '/') && ! Str::startsWith($link, '//')) {
            return true;
        }

        return filter_var($link, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($link, PHP_URL_SCHEME), ['http', 'https'], true);
    }

    protected function randomIndex(int $maximum): int
    {
        return random_int(0, $maximum);
    }

    /**
     * @param  array<int, mixed>  $banners
     * @return array<int, array{image: string, link: ?string}>
     */
    private function normalize(array $banners): array
    {
        $normalized = [];

        foreach ($banners as $banner) {
            if (! is_array($banner)) {
                continue;
            }

            $image = $banner['image'] ?? null;

            if (! is_string($image) || trim($image) === '') {
                continue;
            }

            $link = $banner['link'] ?? null;
            $link = is_string($link) ? trim($link) : null;

            $normalized[] = [
                'image' => trim($image),
                'link' => $this->isAllowedLink($link) ? $link : null,
            ];
        }

        return $normalized;
    }

    /**
     * @return array<int, array{image: string, link: ?string}>
     */
    private function legacyBanners(): array
    {
        $settings = SiteSetting::query()
            ->whereIn('key', [
                config('catalog_banner.legacy_image_setting_key'),
                config('catalog_banner.legacy_link_setting_key'),
            ])
            ->pluck('value', 'key');

        $image = $settings->get(config('catalog_banner.legacy_image_setting_key'));

        if (! is_string($image) || trim($image) === '') {
            return [];
        }

        $link = $settings->get(config('catalog_banner.legacy_link_setting_key'));
        $link = is_string($link) ? trim($link) : null;

        return [[
            'image' => trim($image),
            'link' => $this->isAllowedLink($link) ? $link : null,
        ]];
    }

    private function isManagedPath(string $path): bool
    {
        $directory = trim((string) config('catalog_banner.directory', 'site/banners'), '/');

        return $directory !== '' && Str::startsWith($path, $directory.'/');
    }
}
