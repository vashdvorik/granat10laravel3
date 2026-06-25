<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\Product;
use App\Services\ProductLocalModerationAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLocalModerationAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    private ProductLocalModerationAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyzer = app(ProductLocalModerationAnalyzer::class);
    }

    public function test_contact_in_title_is_blocking(): void
    {
        $product = Product::factory()->create([
            'name' => 'Ноутбук, звоните +373 777 12345',
            'desc' => 'Рабочий ноутбук в хорошем состоянии, полный комплект.',
        ]);

        $result = $this->analyzer->analyze($product);

        $this->assertTrue(collect($result['findings'])->contains(
            fn (array $finding): bool => $finding['code'] === 'contact_in_title' && $finding['blocking']
        ));
    }

    public function test_product_characteristics_are_not_treated_as_phone_number(): void
    {
        $product = Product::factory()->create([
            'name' => 'Наушники Gembird MHP-EP-001-B (вкладыши, 0.9м, 50-18000Гц) blue',
            'desc' => 'Проводные наушники с частотным диапазоном от 50 до 18000 Гц.',
        ]);

        $result = $this->analyzer->analyze($product);

        $this->assertFalse(collect($result['findings'])->contains(
            fn (array $finding): bool => $finding['code'] === 'contact_in_title'
        ));
    }

    public function test_dangerous_pattern_requires_review_but_does_not_auto_reject(): void
    {
        $product = Product::factory()->create([
            'name' => 'Коллекционный предмет',
            'desc' => 'Предлагаются боеприпасы для частной коллекции.',
        ]);

        $result = $this->analyzer->analyze($product);

        $this->assertSame('review', $result['decision']);
        $this->assertSame('weapons', $result['findings'][0]['code']);
        $this->assertFalse($result['findings'][0]['blocking']);
    }

    public function test_short_description_is_non_blocking_quality_hint(): void
    {
        $product = Product::factory()->create([
            'name' => 'Стул',
            'desc' => 'Хороший',
        ]);

        $result = $this->analyzer->analyze($product);

        $finding = collect($result['findings'])->firstWhere('code', 'insufficient_description');
        $this->assertNotNull($finding);
        $this->assertFalse($finding['blocking']);
        $this->assertSame('allow', $result['decision']);
    }

    public function test_fingerprint_is_stable_and_changes_with_content(): void
    {
        $product = Product::factory()->create([
            'name' => 'Стол',
            'desc' => 'Деревянный стол для кухни в хорошем состоянии.',
        ]);
        Image::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/table.webp',
            'order' => 1,
        ]);

        $first = $this->analyzer->fingerprint($product);
        $second = $this->analyzer->fingerprint($product->fresh());

        $this->assertSame($first, $second);

        $product->forceFill(['name' => 'Другой стол'])->save();

        $this->assertNotSame($first, $this->analyzer->fingerprint($product->fresh()));
    }
}
