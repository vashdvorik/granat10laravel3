<?php

namespace Tests\Feature;

use App\Models\Seller;
use App\Models\User;
use App\Services\CabinetDesignManager;
use App\Services\ShopQrPoster;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** QR-код магазина для печати: ведёт на публичную страницу магазина, оформлен в стиле BIMM. */
class ShopQrTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string}> */
    public static function themes(): array
    {
        return ['legacy' => ['legacy'], 'new' => ['new']];
    }

    /** @return array<string, array{0: string}> */
    public static function layouts(): array
    {
        return ['poster' => [ShopQrPoster::POSTER], 'sticker' => [ShopQrPoster::STICKER]];
    }

    private function sellerUser(array $seller = []): User
    {
        $user = User::factory()->create();
        Seller::factory()->create(array_merge([
            'user_id' => $user->id, 'name' => 'Китайца', 'alias' => 'kitaica', 'town' => 'Тирасполь',
        ], $seller));

        return $user->fresh();
    }

    private function poster(): ShopQrPoster
    {
        return app(ShopQrPoster::class);
    }

    // ------------------------------------------------------------------ сервис

    public function test_qr_points_to_the_public_shop_page(): void
    {
        $seller = $this->sellerUser()->seller;

        $this->assertSame(route('seller', ['alias' => 'kitaica']), $this->poster()->url($seller));
        $this->assertStringEndsWith('/shops/kitaica', $this->poster()->displayUrl($seller));
        $this->assertStringNotContainsString('http', $this->poster()->displayUrl($seller));
    }

    /** @dataProvider layouts */
    public function test_svg_is_valid_xml_with_shop_name_and_bimm_branding(string $layout): void
    {
        $seller = $this->sellerUser()->seller;
        $svg = $this->poster()->svg($seller, $layout);

        $document = new DOMDocument();
        $this->assertTrue($document->loadXML($svg), 'SVG должен быть корректным XML');
        $this->assertSame('svg', $document->documentElement->nodeName);

        $this->assertStringContainsString('Китайца', $svg);
        $this->assertStringContainsString('BIMM', $svg);
        // фирменный градиент BIMM и тёмный модуль кода
        $this->assertStringContainsString('#b965ed', $svg);
        $this->assertStringContainsString('#4ed9ee', $svg);
        $this->assertStringContainsString('<path d="M', $svg);
    }

    public function test_poster_shows_town_and_printed_address(): void
    {
        $seller = $this->sellerUser()->seller;
        $svg = $this->poster()->svg($seller, ShopQrPoster::POSTER);

        $this->assertStringContainsString('Тирасполь', $svg);
        $this->assertStringContainsString($this->poster()->displayUrl($seller), $svg);
        $this->assertStringContainsString('Наведите камеру телефона на код', $svg);
    }

    public function test_inline_svg_has_no_xml_prolog(): void
    {
        $seller = $this->sellerUser()->seller;

        $this->assertStringStartsWith('<?xml', $this->poster()->svg($seller));
        $this->assertStringStartsWith('<svg', $this->poster()->inlineSvg($seller));
    }

    /** @dataProvider layouts */
    public function test_special_characters_in_shop_name_are_escaped(string $layout): void
    {
        $seller = $this->sellerUser(['name' => 'Электроника & <Плюс> "Лучшая"'])->seller;
        $svg = $this->poster()->svg($seller, $layout);

        $this->assertTrue((new DOMDocument())->loadXML($svg));
        $this->assertStringContainsString('&amp;', $svg);
        $this->assertStringNotContainsString('<Плюс>', $svg);
    }

    /** @dataProvider layouts */
    public function test_very_long_name_is_wrapped_and_truncated(string $layout): void
    {
        $name = str_repeat('Очень длинное название магазина ', 8);
        $seller = $this->sellerUser(['name' => $name])->seller;
        $svg = $this->poster()->svg($seller, $layout);

        $this->assertTrue((new DOMDocument())->loadXML($svg));
        $this->assertStringContainsString('…', $svg);
        $this->assertStringNotContainsString(trim($name), $svg);
    }

    public function test_qr_matrix_changes_with_the_address(): void
    {
        $first = $this->poster()->svg($this->sellerUser(['alias' => 'first-shop'])->seller);
        $second = $this->poster()->svg($this->sellerUser(['alias' => 'second-shop'])->seller);

        preg_match('/<path d="(M[^"]+)" fill="#1d1d24"/', $first, $a);
        preg_match('/<path d="(M[^"]+)" fill="#1d1d24"/', $second, $b);

        $this->assertNotEmpty($a[1] ?? null);
        $this->assertNotSame($a[1], $b[1] ?? null);
    }

    public function test_filename_is_ascii_and_describes_the_layout(): void
    {
        $seller = $this->sellerUser(['alias' => 'moy-magazin'])->seller;

        $this->assertSame('bimm-qr-moy-magazin-poster.png', $this->poster()->filename($seller, ShopQrPoster::POSTER, 'png'));
        $this->assertSame('bimm-qr-moy-magazin-nakleika.svg', $this->poster()->filename($seller, ShopQrPoster::STICKER, 'svg'));
    }

    // ------------------------------------------------------------------ страница кабинета

    /** @dataProvider themes */
    public function test_cabinet_page_shows_both_layouts_and_actions(string $theme): void
    {
        app(CabinetDesignManager::class)->setActive($theme);
        $user = $this->sellerUser();

        $html = $this->actingAs($user)->get(route('account.store.qr'))
            ->assertOk()
            ->assertSee('QR-код магазина')
            ->assertSee('Плакат А4')
            ->assertSee('Наклейка')
            ->assertSee('Скачать PNG')
            ->assertSee('Скачать SVG')
            ->assertSee('Распечатать')
            ->assertSee('Скопировать ссылку')
            ->assertSee(route('account.store.qr.download', ['layout' => 'poster']), false)
            ->assertSee(route('account.store.qr.download', ['layout' => 'sticker']), false)
            ->assertSee('data-qr-preview="poster"', false)
            ->assertSee('data-qr-preview="sticker"', false)
            ->assertSee('Китайца')
            ->getContent();

        // рисунок вставлен прямо в страницу, без XML-пролога
        $this->assertStringNotContainsString('<?xml', $html);
        $this->assertStringContainsString(route('seller', ['alias' => 'kitaica']), $html);
    }

    /** @dataProvider themes */
    public function test_menu_item_is_present_for_sellers_in_both_cabinet_themes(string $theme): void
    {
        app(CabinetDesignManager::class)->setActive($theme);

        $this->actingAs($this->sellerUser())->get(route('account.index'))
            ->assertOk()
            ->assertSee('href="'.route('account.store.qr').'"', false)
            ->assertSee('QR-код магазина');
    }

    public function test_menu_item_is_hidden_from_users_without_a_shop(): void
    {
        app(CabinetDesignManager::class)->setActive('new');

        $this->actingAs(User::factory()->create())->get(route('account.index'))
            ->assertOk()
            ->assertDontSee(route('account.store.qr'), false);
    }

    // ------------------------------------------------------------------ скачивание и доступ

    /** @dataProvider layouts */
    public function test_svg_download_is_an_attachment_with_a_clean_filename(string $layout): void
    {
        $user = $this->sellerUser();

        $response = $this->actingAs($user)
            ->get(route('account.store.qr.download', ['layout' => $layout]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');

        $this->assertStringContainsString(
            'attachment; filename="'.$this->poster()->filename($user->seller, $layout, 'svg').'"',
            $response->headers->get('Content-Disposition'),
        );
        $this->assertTrue((new DOMDocument())->loadXML($response->getContent()));
        $this->assertStringContainsString('Китайца', $response->getContent());
    }

    public function test_unknown_layout_is_not_found(): void
    {
        $this->actingAs($this->sellerUser())
            ->get('/account/store/qr/download/banner')
            ->assertNotFound();
    }

    public function test_user_without_a_shop_is_sent_to_create_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('account.store.qr'))
            ->assertRedirect(route('account.store.create'))
            ->assertSessionHas('error');

        $this->actingAs($user)->get(route('account.store.qr.download', ['layout' => 'poster']))
            ->assertRedirect(route('account.store.create'));
    }

    public function test_guests_cannot_open_the_page_or_download(): void
    {
        $this->get(route('account.store.qr'))->assertRedirect(route('login'));
        $this->get(route('account.store.qr.download', ['layout' => 'poster']))->assertRedirect(route('login'));
    }

    public function test_seller_only_gets_their_own_shop_code(): void
    {
        $owner = $this->sellerUser(['name' => 'Первый магазин', 'alias' => 'first']);
        $other = $this->sellerUser(['name' => 'Второй магазин', 'alias' => 'second']);

        $svg = $this->actingAs($owner)
            ->get(route('account.store.qr.download', ['layout' => 'poster']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Первый магазин', $svg);
        $this->assertStringNotContainsString('Второй магазин', $svg);
        $this->assertNotSame($owner->seller->alias, $other->seller->alias);
    }
}
