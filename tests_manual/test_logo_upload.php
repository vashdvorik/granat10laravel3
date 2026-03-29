<?php
/**
 * Тест обработки изображения логотипа магазина.
 * Запуск: php tests_manual/test_logo_upload.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Intervention\Image\ImageManager;

$manager = new ImageManager(['driver' => 'gd']);

// -------------------------------------------------------
// 1) Создаём тестовое изображение 1200 × 800 (не квадрат)
// -------------------------------------------------------
$testWidth  = 1200;
$testHeight = 800;

$img = $manager->canvas($testWidth, $testHeight, '#3498db');
// Рисуем текст для наглядности
$img->rectangle(0, 0, $testWidth - 1, $testHeight - 1, function ($draw) {
    $draw->border(5, '#e74c3c');
});

// Сохраняем исходное PNG для сравнения
$originalPng = __DIR__ . '/logo_original.png';
$img->encode('png', 100)->save($originalPng);
$originalSize = filesize($originalPng);

echo "=== ТЕСТ ОБРАБОТКИ ЛОГОТИПА МАГАЗИНА ===" . PHP_EOL . PHP_EOL;
echo "1) Исходное изображение: {$testWidth}×{$testHeight} px, " . round($originalSize / 1024) . " KB (PNG)" . PHP_EOL;

// -------------------------------------------------------
// 2) Применяем ту же обработку, что в контроллере
// -------------------------------------------------------
$processed = $manager->make($originalPng)
    ->orientate()
    ->fit(400, 400, function ($c) { $c->upsize(); })
    ->encode('webp', 85);

$outputWebp = __DIR__ . '/logo_processed.webp';
file_put_contents($outputWebp, (string) $processed);
$processedSize = filesize($outputWebp);

// Получаем размеры результата
$checkImg = $manager->make($outputWebp);
$resultW  = $checkImg->width();
$resultH  = $checkImg->height();

echo "2) После fit(400,400) + WebP 85%: {$resultW}×{$resultH} px, " . round($processedSize / 1024) . " KB" . PHP_EOL . PHP_EOL;

// -------------------------------------------------------
// 3) Проверки
// -------------------------------------------------------
$pass = true;

$check = function (string $label, bool $cond) use (&$pass) {
    $icon = $cond ? '✅' : '❌';
    echo "  {$icon}  {$label}" . PHP_EOL;
    if (!$cond) $pass = false;
};

$check("Ширина обрезана до 400 px (было {$testWidth})", $resultW === 400);
$check("Высота обрезана до 400 px (было {$testHeight})", $resultH === 400);
$check("Квадратное кадрирование (fit)", $resultW === $resultH);
$check("Размер уменьшился (сжатие в WebP)", $processedSize < $originalSize);
$check("Файл WebP создан", file_exists($outputWebp) && $processedSize > 0);

echo PHP_EOL;
echo $pass
    ? "🎉  ВСЕ ПРОВЕРКИ ПРОШЛИ — изображение сжимается и обрезается корректно." . PHP_EOL
    : "⚠️  ЕСТЬ ОШИБКИ — проверьте вывод выше." . PHP_EOL;

echo PHP_EOL . "Файлы для визуальной проверки:" . PHP_EOL;
echo "  Оригинал : {$originalPng}" . PHP_EOL;
echo "  Результат: {$outputWebp}" . PHP_EOL;
