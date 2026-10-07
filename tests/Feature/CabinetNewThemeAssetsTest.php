<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Models\User;
use App\Services\CabinetDesignManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CabinetNewThemeAssetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(CabinetDesignManager::class)->setActive('new');
    }

    public function test_every_local_asset_of_new_cabinet_layout_exists(): void
    {
        $user = User::factory()->create();
        Seller::factory()->create(['user_id' => $user->id]);

        $html = $this->actingAs($user)->get(route('account.index'))->assertOk()->getContent();

        preg_match_all('/(?:src|href)="(https?:\/\/[^"\s]+)"/', $html, $matches);
        $appUrl = rtrim(config('app.url'), '/');
        $checked = 0;

        foreach (array_unique($matches[1]) as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?: '';

            // Только локальные файлы приложения (css/js/img); внешние CDN и маршруты не проверяем
            if (! preg_match('/^\/(css|js|cabinet|img|auth)\/.+\.(css|js|png|jpg|svg)$/', $path)) {
                continue;
            }

            if (parse_url($url, PHP_URL_HOST) !== parse_url($appUrl, PHP_URL_HOST) && ! str_starts_with($url, 'http://localhost')) {
                continue;
            }

            $this->assertFileExists(public_path(ltrim($path, '/')), "Файл из layout не найден: {$path}");
            $checked++;
        }

        $this->assertGreaterThan(3, $checked, 'Должны быть проверены подключённые css/js кабинета');
    }

    public function test_new_cabinet_layout_has_logout_bootstrap_and_confirm_dialogs(): void
    {
        $user = User::factory()->create();
        Seller::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('cabinet-logout', false)
            ->assertSee('id="logout-form"', false)
            ->assertSee('bootstrap.min.js', false)
            ->assertSee('sweetalert2', false)
            ->assertSee('cabinet-common.css', false)
            ->assertDontSee('fa-grid-2', false);
    }

    public function test_only_one_sidebar_item_is_active_on_password_page(): void
    {
        $user = User::factory()->create();
        Seller::factory()->create(['user_id' => $user->id]);

        $html = $this->actingAs($user)
            ->get(route('account.user.change_password'))
            ->assertOk()
            ->getContent();

        preg_match_all('/<a class="active" href="([^"]+)"/', $html, $matches);

        $this->assertCount(1, $matches[1]);
        $this->assertStringContainsString('change-password', $matches[1][0]);
    }

    public function test_orange_cabinet_stylesheets_keep_grid_and_palette_bridge(): void
    {
        $theme = file_get_contents(public_path('css/theme-cabinet-new.css'));
        $shared = file_get_contents(public_path('css/cabinet-common.css'));

        // Bootstrap-сетка не должна ломаться переопределением .row / .col-*
        $this->assertStringNotContainsString('.cabinet-new .row > [class*=col-]', $theme);
        $this->assertStringContainsString('Cabinet polish (orange)', $theme);
        $this->assertStringContainsString('--seller-cabinet-accent: var(--temu-orange)', $theme);

        // Один общий файл обслуживает обе темы: правила привязаны к классам body обеих тем
        $this->assertStringContainsString('body.seller-cabinet-body', $shared);
        $this->assertStringContainsString(':where(body.cabinet-new)', $shared);
    }
}
