<?php

namespace App\Services;

use App\Models\Seller;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Support\Str;

/**
 * QR-код магазина в фирменном стиле BIMM для печати: постер А4 и квадратная наклейка.
 * Всё рисуется векторно (SVG), поэтому печатается без потери качества на любом размере.
 * Код ведёт на публичную страницу магазина.
 */
class ShopQrPoster
{
    public const POSTER = 'poster';

    public const STICKER = 'sticker';

    public const LAYOUTS = [self::POSTER, self::STICKER];

    private const FONT = "'Helvetica Neue','Segoe UI',Arial,Helvetica,sans-serif";

    // Фирменные цвета BIMM (логотип и градиент витрины)
    private const PURPLE = '#7f3bc3';

    private const ACCENT = '#8750b4';

    private const GRADIENT_FROM = '#b965ed';

    private const GRADIENT_TO = '#4ed9ee';

    private const INK = '#1d1d24';

    public function url(Seller $seller): string
    {
        return route('seller', ['alias' => $seller->alias]);
    }

    /** Адрес без «https://»: именно так его печатаем под кодом. */
    public function displayUrl(Seller $seller): string
    {
        return (string) preg_replace('#^https?://#i', '', $this->url($seller));
    }

    public function filename(Seller $seller, string $layout, string $extension): string
    {
        $slug = Str::slug((string) ($seller->alias ?: $seller->getKey())) ?: 'shop';

        return 'bimm-qr-'.$slug.'-'.($layout === self::STICKER ? 'nakleika' : 'poster').'.'.$extension;
    }

    public function svg(Seller $seller, string $layout = self::POSTER): string
    {
        return $layout === self::STICKER ? $this->sticker($seller) : $this->poster($seller);
    }

    /** Тот же рисунок без XML-пролога: его можно вставлять прямо в HTML-страницу. */
    public function inlineSvg(Seller $seller, string $layout = self::POSTER): string
    {
        return (string) preg_replace('/^<\?xml[^>]*\?>\s*/', '', $this->svg($seller, $layout));
    }

    // ------------------------------------------------------------------ постер А4

    private function poster(Seller $seller): string
    {
        $width = 1000;
        $height = 1414;
        $name = $this->shopName($seller);
        [$size, $lines] = $this->fitName($name, 800, [[92, 2], [76, 2], [62, 3], [52, 3]]);
        $lineHeight = (int) round($size * 1.1);

        $nameTop = 330;
        $textBottom = $nameTop + count($lines) * $lineHeight - 10;
        $town = trim((string) $seller->town);

        $y = $textBottom;
        $townSvg = '';

        if ($town !== '') {
            $y += 44;
            $townSvg = $this->text(500, $y, $town, 32, '600', '#6b6f7b', 'middle');
        }

        $cardTop = max(560, $y + 48);
        $cardSize = (int) min(640, 1176 - $cardTop);
        $cardLeft = (int) (($width - $cardSize) / 2);
        $inner = $cardSize - 20;

        $qr = $this->qrPath($this->url($seller), $cardLeft + 10, $cardTop + 10, $inner);

        $nameSvg = '';
        foreach ($lines as $index => $line) {
            $nameSvg .= $this->text(500, $nameTop + $index * $lineHeight + $size * 0.82, $line, $size, '800', '#222222', 'middle');
        }

        $pillTop = $cardTop + $cardSize + 40;
        $display = $this->displayUrl($seller);
        $urlSize = (int) max(22, min(34, 34 - max(0, mb_strlen($display) - 26)));

        return $this->wrap($width, $height, 'p', implode('', [
            '<rect width="1000" height="1414" fill="url(#p-bg)"/>',
            '<rect width="1000" height="18" fill="url(#p-brand)"/>',
            $this->logo(500, 70, 1.0),
            $this->text(500, 262, 'МАГАЗИН НА BIMM', 26, '700', self::ACCENT, 'middle', 'letter-spacing="6"'),
            $nameSvg,
            $townSvg,
            sprintf('<rect x="%d" y="%d" width="%d" height="%d" rx="52" fill="url(#p-brand)" filter="url(#p-shadow)"/>', $cardLeft, $cardTop, $cardSize, $cardSize),
            sprintf('<rect x="%d" y="%d" width="%d" height="%d" rx="44" fill="#ffffff"/>', $cardLeft + 10, $cardTop + 10, $inner, $inner),
            sprintf('<path d="%s" fill="%s" shape-rendering="crispEdges"/>', $qr, self::INK),
            sprintf('<rect x="150" y="%d" width="700" height="76" rx="38" fill="url(#p-brand)"/>', $pillTop),
            $this->text(500, $pillTop + 50, 'Наведите камеру телефона на код', 32, '800', '#ffffff', 'middle'),
            $this->text(500, 1332, $display, $urlSize, '800', self::ACCENT, 'middle'),
            $this->text(500, 1374, 'BIMM — товары и магазины Приднестровья', 22, '500', '#8a8f9c', 'middle'),
        ]));
    }

    // ------------------------------------------------------------------ квадратная наклейка

    private function sticker(Seller $seller): string
    {
        $width = 900;
        $height = 1000;
        [$size, $lines] = $this->fitName($this->shopName($seller), 720, [[56, 2], [46, 2], [40, 2]]);
        $lineHeight = (int) round($size * 1.12);

        $qr = $this->qrPath($this->url($seller), 150, 150, 600);

        $nameSvg = '';
        foreach ($lines as $index => $line) {
            $nameSvg .= $this->text(450, 800 + $index * $lineHeight, $line, $size, '800', '#222222', 'middle');
        }

        return $this->wrap($width, $height, 's', implode('', [
            '<rect width="900" height="1000" fill="#ffffff"/>',
            '<rect x="14" y="14" width="872" height="972" rx="64" fill="none" stroke="url(#s-brand)" stroke-width="16"/>',
            $this->logo(450, 52, 0.62),
            '<rect x="150" y="150" width="600" height="600" rx="36" fill="#ffffff"/>',
            sprintf('<path d="%s" fill="%s" shape-rendering="crispEdges"/>', $qr, self::INK),
            $nameSvg,
            $this->text(450, 950, 'Сканируйте — смотрите товары', 28, '700', self::ACCENT, 'middle'),
        ]));
    }

    // ------------------------------------------------------------------ общие части

    private function wrap(int $width, int $height, string $id, string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 %1$d %2$d" width="%1$d" height="%2$d" font-family="%3$s" role="img" aria-label="QR-код магазина BIMM">',
                $width,
                $height,
                htmlspecialchars(self::FONT, ENT_QUOTES | ENT_XML1, 'UTF-8'),
            )
            .'<defs>'
            .sprintf('<linearGradient id="%1$s-bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f4eaff"/><stop offset=".55" stop-color="#edfaff"/><stop offset="1" stop-color="#ffffff"/></linearGradient>', $id)
            .sprintf('<linearGradient id="%1$s-brand" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="%2$s"/><stop offset="1" stop-color="%3$s"/></linearGradient>', $id, self::GRADIENT_FROM, self::GRADIENT_TO)
            .sprintf('<filter id="%1$s-shadow" x="-15%%" y="-15%%" width="130%%" height="140%%"><feDropShadow dx="0" dy="16" stdDeviation="22" flood-color="#5a2d82" flood-opacity=".18"/></filter>', $id)
            .'</defs>'
            .$body
            .'</svg>';
    }

    /** Фирменный знак: фиолетовая иконка с меткой и слово BIMM. Центрируется по горизонтали около $centerX. */
    private function logo(int $centerX, int $top, float $scale): string
    {
        $iconSize = 84;
        $gap = 20;
        $wordWidth = 262;
        $total = ($iconSize + $gap + $wordWidth) * $scale;
        $left = $centerX - $total / 2;

        return sprintf(
            '<g transform="translate(%s %s) scale(%s)">'
            .'<rect width="84" height="84" rx="22" fill="%s"/>'
            .'<path d="M42 17c-11 0-20 8.8-20 19.7 0 13.8 16.4 27.6 18.9 29.7a1.7 1.7 0 0 0 2.2 0C45.6 64.3 62 50.5 62 36.7 62 25.8 53 17 42 17z" fill="#ffffff"/>'
            .'<circle cx="42" cy="37" r="8.5" fill="%s"/>'
            .'<text x="%d" y="70" font-size="92" font-weight="900" letter-spacing="-2" fill="#111111">BIMM</text>'
            .'</g>',
            $this->num($left),
            $this->num($top),
            $this->num($scale),
            self::PURPLE,
            self::PURPLE,
            $iconSize + $gap,
        );
    }

    private function text(float $x, float $y, string $content, int|float $size, string $weight, string $fill, string $anchor = 'start', string $extra = ''): string
    {
        return sprintf(
            '<text x="%s" y="%s" font-size="%s" font-weight="%s" fill="%s" text-anchor="%s"%s>%s</text>',
            $this->num($x),
            $this->num($y),
            $this->num((float) $size),
            $weight,
            $fill,
            $anchor,
            $extra === '' ? '' : ' '.$extra,
            htmlspecialchars($content, ENT_QUOTES | ENT_XML1, 'UTF-8'),
        );
    }

    /**
     * Модули QR-кода одним контуром. $size — сторона области вместе с «тихой зоной» в 4 модуля:
     * без неё телефоны хуже находят код.
     */
    private function qrPath(string $content, float $x, float $y, float $size): string
    {
        $quiet = 4;
        $matrix = Encoder::encode($content, ErrorCorrectionLevel::Q(), 'UTF-8')->getMatrix();
        $count = $matrix->getWidth();
        $unit = $size / ($count + 2 * $quiet);
        $path = '';

        for ($row = 0; $row < $count; $row++) {
            $col = 0;

            while ($col < $count) {
                if ($matrix->get($col, $row) !== 1) {
                    $col++;

                    continue;
                }

                $start = $col;

                while ($col < $count && $matrix->get($col, $row) === 1) {
                    $col++;
                }

                $run = ($col - $start) * $unit;
                $path .= sprintf(
                    'M%s %sh%sv%sh-%sz',
                    $this->num($x + ($start + $quiet) * $unit),
                    $this->num($y + ($row + $quiet) * $unit),
                    $this->num($run),
                    $this->num($unit),
                    $this->num($run),
                );
            }
        }

        return $path;
    }

    private function shopName(Seller $seller): string
    {
        $name = trim((string) $seller->name, "\"'«» \t\n\r");

        return $name !== '' ? $name : 'Магазин';
    }

    /**
     * Подбирает размер шрифта так, чтобы название влезло в заданное число строк.
     *
     * @param  array<int, array{0: int, 1: int}>  $sizes  от крупного к мелкому: [размер, допустимо строк]
     * @return array{0: int, 1: array<int, string>}
     */
    private function fitName(string $name, int $width, array $sizes): array
    {
        foreach ($sizes as [$size, $maxLines]) {
            $lines = $this->wrapLines($name, (int) floor($width / ($size * 0.6)), 8);

            if (count($lines) <= $maxLines) {
                return [$size, $lines];
            }
        }

        [$size, $maxLines] = end($sizes);

        return [$size, $this->wrapLines($name, (int) floor($width / ($size * 0.6)), $maxLines)];
    }

    /** @return array<int, string> */
    private function wrapLines(string $text, int $maxChars, int $maxLines): array
    {
        $maxChars = max(6, $maxChars);
        $lines = [];
        $line = '';

        foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
            while (mb_strlen($word) > $maxChars) {
                if ($line !== '') {
                    $lines[] = $line;
                    $line = '';
                }

                $lines[] = mb_substr($word, 0, $maxChars);
                $word = mb_substr($word, $maxChars);
            }

            $candidate = $line === '' ? $word : $line.' '.$word;

            if (mb_strlen($candidate) <= $maxChars) {
                $line = $candidate;
            } else {
                $lines[] = $line;
                $line = $word;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $last = rtrim($lines[$maxLines - 1], '.,;:!? ');
            $lines[$maxLines - 1] = mb_substr($last, 0, $maxChars - 1).'…';
        }

        return $lines;
    }

    private function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') ?: '0';
    }
}
