<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Services\DesignManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** На странице распродаж нет видимого блока «Акции в ПМР / Показано N из M», но главный заголовок остаётся для поисковиков. */
class SalePageHeadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_page_has_no_visible_heading_block_but_keeps_hidden_h1(): void
    {
        app(DesignManager::class)->setActive('legacy');
        $seller = Seller::factory()->create();
        Product::factory()->create([
            'seller_id' => $seller->id, 'category_id' => Category::factory()->create()->id,
            'price' => 1000, 'lowprice' => 700, 'sale_started_at' => now(),
        ]);

        $html = $this->get(route('catalog.sale'))->assertOk()->getContent();

        $this->assertStringNotContainsString('sale-page__heading', $html);
        $this->assertStringNotContainsString('Показано', $html);
        $this->assertStringContainsString('<h1 class="sr-only">Скидки и акции в ПМР</h1>', $html);
        $this->assertSame(1, substr_count($html, '<h1'));
    }
}
