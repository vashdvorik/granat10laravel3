<?php

namespace Tests\Feature;

use App\Services\DesignManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    use RefreshDatabase;

    /** @dataProvider designs */
    public function test_404_page_loads_picture_by_absolute_url_on_nested_addresses(string $design): void
    {
        Cache::flush();
        app(DesignManager::class)->setActive($design);

        $html = $this->get('/catalog/net-takoy-kategorii/eshche/glubzhe')->assertNotFound()->getContent();

        // Относительный путь вида src="img/404.png" на вложенных адресах превращался в /catalog/…/img/404.png
        $this->assertDoesNotMatchRegularExpression('#src="img/#', $html);
    }

    public function test_legacy_404_uses_absolute_picture_url(): void
    {
        Cache::flush();
        app(DesignManager::class)->setActive('legacy');

        $this->get('/catalog/a/b/c')->assertNotFound()->assertSee(asset('img/404.png'), false);
    }

    /** @return array<int, array{string}> */
    public static function designs(): array
    {
        return [['legacy'], ['new'], ['joom']];
    }
}
