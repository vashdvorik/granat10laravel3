<?php

namespace App\Support;

/**
 * Адрес CSS/JS-файла из public с датой изменения: /css/style.css?v=1793029045.
 *
 * Хостинг отдаёт статику с кешем браузера на месяц. Без номера версии посетители месяцами видят старые стили,
 * а с ним браузер скачивает файл заново сразу после выкладки. Нужен в шаблонах через @assetv('css/style.css').
 */
final class AssetVersion
{
    /** @var array<string, int|null> */
    private static array $mtimes = [];

    public static function url(string $path): string
    {
        $path = ltrim($path, '/');

        if (! array_key_exists($path, self::$mtimes)) {
            $mtime = @filemtime(public_path($path));
            self::$mtimes[$path] = $mtime === false ? null : $mtime;
        }

        $url = asset($path);

        return self::$mtimes[$path] === null ? $url : $url.'?v='.self::$mtimes[$path];
    }
}
