<?php

namespace Tests\Unit;

use App\Services\LocalImageContentScanner;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocalImageContentScannerTest extends TestCase
{
    public function test_scanner_detects_contact_from_ocr_and_assigns_image_index(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/first.jpg', 'image');
        Storage::disk('public')->put('products/second.jpg', 'image');
        config()->set('moderation.images.local_scan.enabled', true);

        $scanner = new class extends LocalImageContentScanner
        {
            protected function ocr(string $path): ?string
            {
                return str_contains($path, 'second.jpg') ? 'WhatsApp +373 60 123 456' : 'clean image';
            }

            protected function qr(string $path): ?string
            {
                return '';
            }
        };

        $result = $scanner->scan(['products/first.jpg', 'products/second.jpg']);

        $this->assertTrue($result['scanned']);
        $this->assertCount(1, $result['violations']);
        $this->assertSame('contact_information', $result['violations'][0]['code']);
        $this->assertSame(2, $result['violations'][0]['image_index']);
    }
}
