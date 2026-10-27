<?php

namespace Tests\Unit;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Названия товаров, категорий и магазинов показываются так, как их ввёл автор.
 *
 * Раньше в стилях сайта у `.product-title`, названий категорий в меню и др. стояло `text-transform: capitalize`,
 * и браузер показывал «Бюстгальтер Без Паралона На Xb Основе» вместо «Бюстгальтер без паралона на XB основе».
 * Тест не даёт вернуть такое приведение регистра в собственные стили, шаблоны и PHP-код.
 */
class NoTitleCaseTest extends TestCase
{
    /** Явная утилита: применяется только там, где её написали в разметке вручную. */
    private const ALLOWED_UTILITY_LINE = '/^\.text-capitalize\s*\{/';

    public function test_site_styles_do_not_title_case_text(): void
    {
        $offenders = [];

        foreach (glob($this->root().'/public/css/*.css') ?: [] as $file) {
            if (str_ends_with($file, '.min.css')) {
                continue;
            }

            $previous = '';

            foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $number => $line) {
                if (preg_match('/text-transform:\s*capitalize/i', $line) && ! preg_match(self::ALLOWED_UTILITY_LINE, $previous)) {
                    $offenders[] = basename($file).':'.($number + 1);
                }

                $previous = $line;
            }
        }

        $this->assertSame([], $offenders, 'В стилях сайта нельзя использовать text-transform: capitalize: он портит названия.');
    }

    public function test_templates_do_not_title_case_text_with_inline_styles(): void
    {
        $offenders = [];

        foreach ($this->files($this->root().'/resources/views', ['php']) as $file) {
            if (preg_match('/text-transform:\s*capitalize/i', (string) file_get_contents($file))) {
                $offenders[] = $this->relative($file);
            }
        }

        $this->assertSame([], $offenders, 'В шаблонах нельзя задавать text-transform: capitalize.');
    }

    public function test_templates_do_not_use_capitalize_classes(): void
    {
        $offenders = [];

        foreach ($this->files($this->root().'/resources/views', ['php']) as $file) {
            // классы Tailwind «capitalize» и Bootstrap «text-capitalize» делают из «Войти в кабинет» «Войти В Кабинет»
            if (preg_match('/class\s*=\s*"[^"]*(?<![\w-])(?:text-)?capitalize(?![\w-])[^"]*"/i', (string) file_get_contents($file))) {
                $offenders[] = $this->relative($file);
            }
        }

        $this->assertSame([], $offenders, 'В шаблонах нельзя использовать классы capitalize и text-capitalize: они портят заголовки и подписи.');
    }
    public function test_php_code_does_not_title_case_text(): void
    {
        $offenders = [];

        foreach ([...$this->files($this->root().'/app', ['php']), ...$this->files($this->root().'/resources/views', ['php'])] as $file) {
            $code = (string) file_get_contents($file);

            if (preg_match('/Str::title\(|Str::headline\(|\bucwords\(|MB_CASE_TITLE/', $code)) {
                $offenders[] = $this->relative($file);
            }
        }

        $this->assertSame([], $offenders, 'Названия нельзя приводить к «Каждое Слово С Большой» в коде.');
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function relative(string $file): string
    {
        return str_replace('\\', '/', substr($file, strlen($this->root()) + 1));
    }

    /** @return list<string> */
    private function files(string $directory, array $extensions): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), $extensions, true)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
