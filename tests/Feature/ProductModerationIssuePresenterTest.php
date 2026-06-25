<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Services\ProductModerationIssuePresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductModerationIssuePresenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_presenter_returns_issues_for_current_product_version(): void
    {
        $product = Product::factory()->create(['moderation_version' => 3]);
        ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => 3,
            'trigger' => 'queue',
            'provider' => 'test',
            'status' => 'completed',
            'request_fingerprint' => hash('sha256', 'current-version'),
            'reasons' => [[
                'code' => 'wrong_category',
                'field' => 'category',
                'evidence' => 'Выбранная категория не соответствует товару.',
                'suggestion' => 'Выберите более точную категорию.',
            ]],
        ]);

        $issues = app(ProductModerationIssuePresenter::class)->forProduct($product);

        $this->assertSame('Категория', $issues[0]['label']);
        $this->assertSame('category-selectors', $issues[0]['target']);
        $this->assertSame('Выберите более точную категорию.', $issues[0]['suggestion']);
    }

    public function test_presenter_ignores_issues_from_old_version(): void
    {
        $product = Product::factory()->create(['moderation_version' => 4]);
        ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => 3,
            'trigger' => 'queue',
            'provider' => 'test',
            'status' => 'completed',
            'request_fingerprint' => hash('sha256', 'old-version'),
            'reasons' => [['evidence' => 'Устаревшее замечание.']],
        ]);

        $this->assertSame([], app(ProductModerationIssuePresenter::class)->forProduct($product));
    }

    public function test_presenter_attaches_image_preview_to_image_issue(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/problem.jpg', 'fake-image');

        $product = Product::factory()->create(['moderation_version' => 2]);
        Image::create([
            'product_id' => $product->id,
            'path' => 'products/problem.jpg',
            'is_main' => true,
            'order' => 1,
        ]);
        ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => 2,
            'trigger' => 'image_queue',
            'provider' => 'image_google',
            'status' => 'completed',
            'request_fingerprint' => hash('sha256', 'image-version'),
            'reasons' => [[
                'code' => 'wrong_image',
                'field' => 'images',
                'image_index' => 1,
                'evidence' => 'Фото не относится к товару.',
                'suggestion' => 'Замените фото товара.',
            ]],
        ]);

        $issues = app(ProductModerationIssuePresenter::class)->forProduct($product);

        $this->assertTrue($issues[0]['is_image_issue']);
        $this->assertSame(1, $issues[0]['image_index']);
        $this->assertStringContainsString('products/problem.jpg', $issues[0]['image_url']);
        $this->assertSame('product-images-section', $issues[0]['target']);
    }
}
