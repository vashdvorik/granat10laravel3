<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class LocalImageContentScanner
{
    /**
     * @param  array<int, string>  $paths
     * @return array{scanned: bool, violations: array<int, array<string, mixed>>}
     */
    public function scan(array $paths): array
    {
        if (! config('moderation.images.local_scan.enabled', true)) {
            return ['scanned' => false, 'violations' => []];
        }

        $violations = [];
        $scanned = false;
        $disk = Storage::disk('public');

        foreach ($paths as $index => $path) {
            if (! $disk->exists($path)) {
                continue;
            }

            $absolutePath = $disk->path($path);
            $outputs = array_filter([
                $this->ocr($absolutePath),
                $this->qr($absolutePath),
            ], fn (?string $value): bool => $value !== null);

            if ($outputs === []) {
                continue;
            }

            $scanned = true;
            $content = implode("\n", $outputs);

            if ($this->containsContact($content)) {
                $violations[] = [
                    'code' => 'contact_information',
                    'confidence' => 0.95,
                    'evidence' => 'На изображении обнаружены контактные данные, ссылка, мессенджер или QR-код.',
                    'field' => 'images',
                    'image_index' => $index + 1,
                    'suggestion' => 'Удалите контакты, ссылки и QR-коды с изображения.',
                ];
            }
        }

        return ['scanned' => $scanned, 'violations' => $violations];
    }

    protected function ocr(string $path): ?string
    {
        return $this->run([
            (string) config('moderation.images.local_scan.tesseract_binary', 'tesseract'),
            $path,
            'stdout',
            '-l',
            (string) config('moderation.images.local_scan.tesseract_language', 'eng'),
        ]);
    }

    protected function qr(string $path): ?string
    {
        return $this->run([
            (string) config('moderation.images.local_scan.zbar_binary', 'zbarimg'),
            '--quiet',
            '--raw',
            $path,
        ]);
    }

    /** @param array<int, string> $command */
    private function run(array $command): ?string
    {
        try {
            $process = new Process($command);
            $process->setTimeout(max(1, (int) config('moderation.images.local_scan.timeout', 15)));
            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            return trim($process->getOutput());
        } catch (ProcessFailedException|ProcessTimedOutException) {
            return null;
        } catch (Throwable) {
            return null;
        }
    }

    private function containsContact(string $content): bool
    {
        return preg_match('/(?:https?:\/\/|www\.|t\.me\/|telegram|whatsapp|viber|mailto:|@[a-z0-9_]{4,}|\+?[0-9][0-9\s().-]{7,}[0-9])/iu', $content) === 1;
    }
}
