<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Settlement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacySeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('design.default', 'legacy');
        config()->set('regionality.enabled', true);
        config()->set('regionality.urls_enabled', false);
        config()->set('regionality.seo_enabled', true);
    }

    public function test_product_description_is_generated_from_product_data_and_the_sellers_city(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Тирасполь']);
        $seller = Seller::factory()->for($settlement)->create(['name' => 'Техномир']);

        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => Category::factory(),
            'alias' => 'seo-headphones',
            'name' => 'Наушники BIMM',
            'price' => 1200,
            'lowprice' => null,
            'desc' => 'Беспроводные наушники с чистым звуком и удобной посадкой.',
        ]);

        $this->get(route('product.show', ['alias' => $product->alias]))
            ->assertOk()
            ->assertSee(
                '<meta name="description" content="Наушники BIMM в Тирасполе — 1 200 руб. Продавец: Техномир. Беспроводные наушники с чистым звуком и удобной посадкой.">',
                false,
            );
    }
}
