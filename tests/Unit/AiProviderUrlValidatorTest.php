<?php

namespace Tests\Unit;

use App\Services\AiProviderUrlValidator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AiProviderUrlValidatorTest extends TestCase
{
    public function test_public_https_url_is_allowed(): void
    {
        (new AiProviderUrlValidator)->validate('https://api.example.test/v1');

        $this->addToAssertionCount(1);
    }

    #[DataProvider('unsafeUrls')]
    public function test_unsafe_url_is_rejected(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AiProviderUrlValidator)->validate($url);
    }

    public static function unsafeUrls(): array
    {
        return [
            'http' => ['http://api.example.test/v1'],
            'localhost' => ['https://localhost/v1'],
            'loopback ipv4' => ['https://127.0.0.1/v1'],
            'private ipv4' => ['https://192.168.1.10/v1'],
            'link local metadata' => ['https://169.254.169.254/latest/meta-data'],
            'loopback ipv6' => ['https://[::1]/v1'],
            'credentials' => ['https://user:secret@api.example.test/v1'],
            'fragment' => ['https://api.example.test/v1#internal'],
        ];
    }
}
