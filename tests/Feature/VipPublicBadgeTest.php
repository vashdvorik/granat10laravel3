<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Services\DesignManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** VIP покупателям не показывается: ни значка на карточке, ни метки на странице товара. Остаётся только преимущество в выдаче. */
class VipPublicBadgeTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string}> */
    public static function themes(): array
    {
        return ['legacy' => ['legacy'], 'new' => ['new'], 'joom' => ['joom']];
    }

    /** @dataProvider themes */
    public function test_buyers_do_not_see_any_vip_mark_on_cards(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'name' => 'Активный VIP', 'is_vip' => true, 'vip_until' => now()->addDays(2)]);
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'name' => 'Бессрочный VIP', 'is_vip' => true, 'vip_until' => null]);
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'name' => 'Обычный товар']);

        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Активный VIP', $html);
        $this->assertStringContainsString('Обычный товар', $html);
        $this->assertStringNotContainsString('__vip"', $html);
        $this->assertStringNotContainsString('VIP-размещение', $html);
        $this->assertStringNotContainsString('★ VIP', $html);
        $this->assertStringNotContainsString('Выбор BIMM', $html);
    }

    /** @dataProvider themes */
    public function test_product_page_does_not_mark_vip(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $vip = Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_vip' => true, 'vip_until' => now()->addDay()]);

        $this->get(route('product.show', $vip->alias))
            ->assertOk()
            ->assertDontSee('vip-inline-tag', false)
            ->assertDontSee('★ VIP', false);
    }

    public function test_vip_still_ranks_first_for_buyers_without_any_mark(): void
    {
        app(DesignManager::class)->setActive('new');
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $plain = Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'name' => 'Обычный товар сверху по дате', 'push_at' => now()]);
        $vip = Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'name' => 'Оплаченный VIP товар', 'push_at' => now()->subDays(5), 'is_vip' => true, 'vip_until' => now()->addDay()]);

        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, $plain->name), strpos($html, $vip->name), 'VIP-товар должен стоять выше обычного.');
    }

    public function test_seller_cabinet_copy_does_not_promise_a_badge(): void
    {
        $user = \App\Models\User::factory()->create();
        Seller::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('account.vip.index'))
            ->assertOk()
            ->assertSee('Покупатели не видят пометки «VIP»')
            ->assertDontSee('появляется значок «VIP»');
    }
}
