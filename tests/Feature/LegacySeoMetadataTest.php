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

    public function test_legacy_product_description_is_generated_from_product_data(): void
    {
        $settlement = Settlement::factory()->create(['name' => 'Тирасполь']);
        $seller = Seller::factory()->for($settlement)->create();


        $product = Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => Category::factory(),
            'alias' => 'seo-headphones',
            'name' => 'Наушники BIMM',
            'desc' => 'Беспроводные наушники с чистым звуком и удобной посадкой.',
        ]);

        $this->get(route('product.show', ['alias' => $product->alias]))
            ->assertOk()
            ->assertSee(
                '<meta name="description" content="Наушники BIMM в ПМР: Беспроводные наушники с чистым звуком и удобной посадкой.">',
                false,
            );
    }
}
