<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeilisearchConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_search_document_contains_filterable_regional_fields(): void
    {
        $category = Category::factory()->create(['name' => 'Телефоны']);
        $seller = Seller::factory()->create(['name' => 'Магазин', 'settlement_id' => null]);
        $product = Product::factory()->create([
            'name' => 'Смартфон',
            'category_id' => $category->id,
            'seller_id' => $seller->id,
            'features1' => 'Красный',
            'is_vip' => true,
        ]);

        $document = $product->toSearchableArray();

        $this->assertSame('Смартфон', $document['name']);
        $this->assertSame($category->id, $document['category_id']);
        $this->assertSame($seller->id, $document['seller_id']);
        $this->assertSame(['Красный'], $document['features']);
        $this->assertSame(1, $document['is_vip']);
    }

    public function test_only_public_current_version_product_is_searchable(): void
    {
        $product = Product::factory()->create([
            'is_active' => false,
            'moderation_status' => ProductModerationStatus::Draft,
        ]);

        $this->assertFalse($product->shouldBeSearchable());
    }

    public function test_meilisearch_index_has_required_settings(): void
    {
        $settings = config('scout.meilisearch.index-settings.'.Product::class);

        $this->assertContains('settlement_id', $settings['filterableAttributes']);
        $this->assertContains('category_id', $settings['filterableAttributes']);
        $this->assertContains('is_vip', $settings['sortableAttributes']);
        $this->assertContains('typo', $settings['rankingRules']);
    }
}
