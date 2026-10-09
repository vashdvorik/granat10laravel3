<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Services\CabinetDesignManager;
use App\Services\DesignManager;
use App\Support\PageTitle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Бренд сайта — BIMM: в заголовках, подвалах, письмах и коде не осталось прежних названий. */
class BrandNameTest extends TestCase
{
    use RefreshDatabase;

    /** Прежние названия, которых не должно быть в видимых местах. */
    private const OLD_NAMES = '/гранат|granat|маркетплейс\s+пмр|laravel/iu';

    private User $user;

    private Seller $seller;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->seller = Seller::factory()->create(['user_id' => $this->user->id, 'name' => 'Китайца', 'alias' => 'kitaica']);
        $category = Category::factory()->create(['name' => 'Посуда', 'alias' => 'posuda']);
        $this->product = Product::factory()->create([
            'seller_id' => $this->seller->id, 'category_id' => $category->id, 'is_active' => true,
            'name' => 'Чайник электрический', 'alias' => 'chaynik',
        ]);
    }

    private function title(string $url): string
    {
        $response = $this->get($url);
        $this->assertContains($response->status(), [200, 404], "Страница {$url} вернула {$response->status()}");

        $this->assertSame(1, preg_match('#<title>(.*?)</title>#s', (string) $response->getContent(), $match), "На {$url} нет <title>");

        return trim(html_entity_decode($match[1]));
    }

    /** @return array<string, array{0: string}> */
    public static function publicThemes(): array
    {
        return ['legacy' => ['legacy'], 'new' => ['new'], 'joom' => ['joom']];
    }

    // ------------------------------------------------------------------ публичный сайт

    /** @dataProvider publicThemes */
    public function test_every_public_page_is_titled_with_the_new_brand(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);

        $pages = [
            '/',
            route('catalog.index', [], false),
            route('catalog.show', 'posuda', false),
            route('product.show', 'chaynik', false),
            route('seller', 'kitaica', false),
            route('shops', [], false),
            route('catalog.sale', [], false),
            route('search', [], false),
            route('search', ['q' => 'чайник'], false),
            route('favorites', [], false),
            route('help', [], false),
            route('gobuyer', [], false),
            route('orders.index', [], false),
            '/net-takoy-stranicy-vovse',
        ];

        foreach ($pages as $url) {
            $title = $this->title($url);

            $this->assertStringEndsWith(' — BIMM', $title, "Заголовок страницы {$url}: «{$title}»");
            $this->assertDoesNotMatchRegularExpression(self::OLD_NAMES, $title, "В заголовке {$url} остался прежний бренд: «{$title}»");
        }
    }

    public function test_main_seo_titles_sell_the_purchase_and_end_with_the_brand(): void
    {
        foreach ([
            '/' => 'Купить в ПМР',
            route('catalog.index', [], false) => 'купить у местных продавцов',
            route('catalog.show', 'posuda', false) => 'Посуда — купить',
            route('product.show', 'chaynik', false) => 'Чайник электрический — купить',
            route('seller', 'kitaica', false) => 'Китайца',
            route('shops', [], false) => 'каталог местных продавцов',
        ] as $url => $phrase) {
            $title = $this->title($url);

            $this->assertStringContainsString($phrase, $title, "Заголовок {$url}: «{$title}»");
            $this->assertStringEndsWith(' — BIMM', $title);
        }
    }

    // ------------------------------------------------------------------ порядок страниц
    public function test_not_found_page_is_not_titled_as_a_sellers_page(): void
    {
        $title = $this->title('/net-takoy-stranicy-vovse');

        $this->assertStringNotContainsString('Продавцы', $title);
    }

    public function test_open_graph_site_name_is_the_brand(): void
    {
        app(DesignManager::class)->setActive('legacy');

        $this->get('/')->assertSee('<meta property="og:site_name" content="BIMM">', false);
    }

    // ------------------------------------------------------------------ вход

    public function test_login_pages_are_titled_with_the_new_brand_and_carry_no_agency_credit(): void
    {
        foreach (['/login' => 'Войти продавцу — BIMM', '/register' => 'Стать продавцом — BIMM', '/forgot-password' => 'Сброс пароля — BIMM'] as $url => $expected) {
            $this->assertSame($expected, $this->title($url));
        }

        $this->get('/login')
            ->assertDontSee('granatlab')
            ->assertDontSee('Рекламное агентство')
            ->assertSee('© '.now()->year);
    }

    // ------------------------------------------------------------------ кабинет

    /** @return array<string, array{0: string}> */
    public static function cabinetThemes(): array
    {
        return ['legacy' => ['legacy'], 'new' => ['new']];
    }

    /** @dataProvider cabinetThemes */
    public function test_cabinet_pages_have_their_own_titles_with_the_cabinet_brand(string $theme): void
    {
        app(CabinetDesignManager::class)->setActive($theme);
        $this->actingAs($this->user);

        $expected = [
            '/account' => 'Обзор магазина — Кабинет BIMM',
            '/account/store/products' => 'Товары — Кабинет BIMM',
            '/account/store/products/create' => 'Новый товар — Кабинет BIMM',
            '/account/store/products/'.$this->product->id => 'Карточка товара — Кабинет BIMM',
            '/account/store/products/'.$this->product->id.'/edit' => 'Редактирование товара — Кабинет BIMM',
            '/account/store/orders' => 'Заказы — Кабинет BIMM',
            '/account/stats' => 'Аналитика — Кабинет BIMM',
            '/account/vip' => 'VIP-размещение — Кабинет BIMM',
            '/account/store/qr' => 'QR-код магазина — Кабинет BIMM',
            '/account/balance' => 'Журнал баланса — Кабинет BIMM',
            '/account/user' => 'Профиль — Кабинет BIMM',
            '/account/user/change-password' => 'Безопасность — Кабинет BIMM',
        ];

        foreach ($expected as $url => $title) {
            $this->assertSame($title, $this->title($url), "Заголовок страницы {$url}");
        }
    }

    public function test_cabinet_has_no_agency_footer_link(): void
    {
        app(CabinetDesignManager::class)->setActive('legacy');

        $this->actingAs($this->user)->get('/account')
            ->assertOk()
            ->assertDontSee('granatlab');
    }

    // ------------------------------------------------------------------ письма

    public function test_mail_header_shows_the_brand_from_app_name(): void
    {
        $html = view('vendor.mail.html.header', ['url' => 'https://bimm.test', 'slot' => ''])->render();

        $this->assertStringContainsString('BIMM', $html);
        $this->assertDoesNotMatchRegularExpression('/гранат|granat/iu', $html);

        config()->set('app.name', 'Другой бренд');
        $this->assertStringContainsString('Другой бренд', view('vendor.mail.html.header', ['url' => 'https://bimm.test', 'slot' => ''])->render());
    }

    public function test_mail_footer_has_no_agency_text(): void
    {
        $this->assertStringNotContainsString('Рекламное агентство', (string) file_get_contents(resource_path('views/vendor/mail/text/message.blade.php')));
    }

    // ------------------------------------------------------------------ название приложения

    public function test_app_name_is_bimm_by_default_for_admin_panel_and_mail(): void
    {
        $this->assertSame('BIMM', config('app.name'));
        $this->assertSame('BIMM', PageTitle::brand());
        $this->assertStringContainsString("env('APP_NAME', 'BIMM')", (string) file_get_contents(config_path('app.php')));
        $this->assertStringContainsString('APP_NAME=BIMM', (string) file_get_contents(base_path('.env.example')));
        $this->assertStringContainsString('MAIL_FROM_NAME="BIMM"', (string) file_get_contents(base_path('.env.example')));
    }

    public function test_page_title_helper_formats_titles(): void
    {
        $this->assertSame('Каталог — BIMM', PageTitle::site('Каталог'));
        $this->assertSame('BIMM', PageTitle::site(''));
        $this->assertSame('BIMM', PageTitle::site(null));
        $this->assertSame('Заказы — Кабинет BIMM', PageTitle::cabinet(null, 'account.store.orders.index'));
        $this->assertSame('Свой заголовок — Кабинет BIMM', PageTitle::cabinet('Свой заголовок', 'account.index'));
        $this->assertSame('Кабинет продавца — Кабинет BIMM', PageTitle::cabinet(null, 'account.something.new'));
        $this->assertSame('Кабинет продавца — Кабинет BIMM', PageTitle::cabinet(null, null));
    }

    // ------------------------------------------------------------------ исходники

    /** @return array<string, array{0: string}> */
    public static function sourceRoots(): array
    {
        return [
            'app' => ['app'],
            'config' => ['config'],
            'routes' => ['routes'],
            'database' => ['database'],
            'resources' => ['resources'],
            'public css' => ['public/css'],
        ];
    }

    /** @dataProvider sourceRoots */
    public function test_old_brand_name_does_not_appear_in_application_sources(string $root): void
    {
        $found = [];

        foreach (File::allFiles(base_path($root)) as $file) {
            if (! in_array($file->getExtension(), ['php', 'js', 'css', 'json', 'svg', 'html', 'txt', 'md', 'xml'], true)) {
                continue;
            }

            // прежнее название встречается в самой миграции, которая чистит старые ключи ИИ? — нет; исключений нет
            $lines = preg_split('/\R/u', (string) file_get_contents($file->getPathname())) ?: [];

            foreach ($lines as $number => $line) {
                if (preg_match('/гранат|granat/iu', $line)) {
                    $found[] = $file->getRelativePathname().':'.($number + 1).' '.mb_substr(trim($line), 0, 100);
                }
            }
        }

        $this->assertSame([], $found, "Остались упоминания прежнего бренда:\n".implode("\n", $found));
    }

    public function test_old_brand_name_does_not_appear_in_the_readme_or_env_example(): void
    {
        foreach (['README.md', '.env.example'] as $file) {
            $this->assertDoesNotMatchRegularExpression('/гранат|granat/iu', (string) file_get_contents(base_path($file)), $file);
        }
    }
}
