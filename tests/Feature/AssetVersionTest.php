<?php

namespace Tests\Feature;

use App\Support\AssetVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Хостинг отдаёт CSS и JS с кешем браузера на месяц. Адрес с датой файла (?v=...) заставляет браузер скачать
 * новую версию сразу после выкладки.
 */
class AssetVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_carries_the_modification_time_of_the_file(): void
    {
        $url = AssetVersion::url('css/marketplace-design.css');

        $this->assertSame(
            asset('css/marketplace-design.css').'?v='.filemtime(public_path('css/marketplace-design.css')),
            $url
        );
    }

    public function test_url_without_the_file_has_no_version(): void
    {
        $this->assertSame(asset('css/no-such-file.css'), AssetVersion::url('css/no-such-file.css'));
    }

    public function test_blade_directive_prints_the_versioned_address(): void
    {
        $html = Blade::render("<link rel=\"stylesheet\" href=\"@assetv('css/style.css')\">");

        $this->assertMatchesRegularExpression('#href="[^"]+/css/style\.css\?v=\d+"#', $html);
    }

    public function test_public_layout_links_every_own_style_and_script_with_a_version(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        // только свои файлы: внешние (cdnjs и другие) версии не получают
        $own = preg_quote(rtrim(url('/'), '/'), '#');
        preg_match_all('#(?:href|src)="('.$own.'/(?:css|js)/[^"?]+\.(?:css|js))(\?[^"]*)?"#', $html, $matches, PREG_SET_ORDER);

        $this->assertNotEmpty($matches);

        foreach ($matches as $match) {
            $this->assertMatchesRegularExpression('/^\?v=\d+$/', $match[2] ?? '', 'Нет версии у '.$match[1]);
        }
    }
}
