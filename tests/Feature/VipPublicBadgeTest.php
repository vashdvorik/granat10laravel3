<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Services\DesignManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Покупатель видит, что товар в VIP: значок на карточке и метка на странице товара во всех темах. */
class VipPublicBadgeTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: string, 1: string}> тема => [класс значка на карточке] */
    public static function themes(): array
    {
        return [
            'legacy' => ['legacy', 'home-product__vip'],
            'new' => ['new', 'bimm-product__vip'],
            'joom' => ['joom', 'joom-product__vip'],
        ];
    }

    /** @dataProvider themes */
    public function test_card_badge_is_shown_only_while_vip_is_active(string $theme, string $badgeClass): void
    {
        app(DesignManager::class)->setActive($theme);
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'name' => 'Активный VIP', 'is_vip' => true, 'vip_until' => now()->addDays(2)]);
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'name' => 'Бессрочный VIP', 'is_vip' => true, 'vip_until' => null]);
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'name' => 'Просроченный VIP', 'is_vip' => true, 'vip_until' => now()->subHour()]);
        Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'name' => 'Обычный товар']);

        $html = $this->get(route('catalog.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Активный VIP', $html);
        $this->assertStringContainsString('Просроченный VIP', $html);
        // значок есть ровно у двух карточек: у товара со сроком и у бессрочного
        $this->assertSame(2, substr_count($html, 'class="'.$badgeClass.'"'));
    }

    /** @dataProvider themes */
    public function test_product_page_marks_active_vip_only(string $theme): void
    {
        app(DesignManager::class)->setActive($theme);
        $seller = Seller::factory()->create();
        $category = Category::factory()->create();
        $active = Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_vip' => true, 'vip_until' => now()->addDay()]);
        $expired = Product::factory()->create(['seller_id' => $seller->id, 'category_id' => $category->id, 'is_vip' => true, 'vip_until' => now()->subMinute()]);

        $this->get(route('product.show', $active->alias))->assertOk()->assertSee('vip-inline-tag', false);
        $this->get(route('product.show', $expired->alias))->assertOk()->assertDontSee('vip-inline-tag', false);
    }
}
