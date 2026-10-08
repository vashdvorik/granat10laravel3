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

    private function productWithReasons(array $reasons, int $images = 1): Product
    {
        Storage::fake('public');
        $product = Product::factory()->create(['moderation_version' => 5]);

        for ($i = 1; $i <= $images; $i++) {
            Storage::disk('public')->put("products/p{$i}.jpg", 'x');
            Image::create(['product_id' => $product->id, 'path' => "products/p{$i}.jpg", 'is_main' => $i === 1, 'order' => $i]);
        }

        ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => 5,
            'trigger' => 'queue',
            'provider' => 'test',
            'status' => 'completed',
            'request_fingerprint' => hash('sha256', uniqid('', true)),
            'reasons' => $reasons,
        ]);

        return $product;
    }

    public function test_same_remark_for_photo_and_name_becomes_one_issue(): void
    {
        $text = 'На изображении представлен мини-холодильник, в то время как в названии указаны наушники Apple AirPods.';
        $tip = 'Приведите название и категорию в соответствие с реальным товаром на фото.';

        $product = $this->productWithReasons([
            ['field' => 'images', 'image_index' => 1, 'evidence' => $text, 'suggestion' => $tip],
            ['field' => 'name', 'image_index' => 1, 'evidence' => $text, 'suggestion' => $tip],
        ]);

        $issues = app(ProductModerationIssuePresenter::class)->forProduct($product);

        $this->assertCount(1, $issues);
        $this->assertSame($text, $issues[0]['message']);
        $this->assertSame($tip, $issues[0]['suggestion']);
        $this->assertSame(['images', 'name'], array_column($issues[0]['fields'], 'field'));
        $this->assertSame(['Фотографии', 'Название'], array_column($issues[0]['fields'], 'label'));
        $this->assertSame([1], array_column($issues[0]['images'], 'index'));
        $this->assertStringContainsString('products/p1.jpg', $issues[0]['images'][0]['url']);
    }

    public function test_repeats_differing_only_by_case_spaces_or_final_dot_are_merged(): void
    {
        $product = $this->productWithReasons([
            ['field' => 'description', 'evidence' => 'Уберите телефон из описания.'],
            ['field' => 'name', 'evidence' => '  уберите   телефон из описания  '],
        ]);

        $issues = app(ProductModerationIssuePresenter::class)->forProduct($product);

        $this->assertCount(1, $issues);
        $this->assertSame(['description', 'name'], array_column($issues[0]['fields'], 'field'));
    }

    public function test_different_remarks_stay_separate_in_original_order(): void
    {
        $product = $this->productWithReasons([
            ['field' => 'name', 'evidence' => 'Уберите телефон из названия.'],
            ['field' => 'description', 'evidence' => 'Добавьте размеры.'],
        ]);

        $issues = app(ProductModerationIssuePresenter::class)->forProduct($product);

        $this->assertSame(['Уберите телефон из названия.', 'Добавьте размеры.'], array_column($issues, 'message'));
    }

    public function test_suggestion_that_repeats_the_remark_is_dropped_and_duplicates_collapse(): void
    {
        $product = $this->productWithReasons([
            ['field' => 'name', 'evidence' => 'Уберите телефон из названия.', 'suggestion' => 'уберите телефон из названия'],
            ['field' => 'name', 'evidence' => 'Уберите телефон из названия.', 'suggestion' => 'Оставьте только название.'],
            ['field' => 'description', 'evidence' => 'Уберите телефон из названия.', 'suggestion' => 'Оставьте только название.'],
        ]);

        $issues = app(ProductModerationIssuePresenter::class)->forProduct($product);

        $this->assertCount(1, $issues);
        $this->assertSame('Оставьте только название.', $issues[0]['suggestion']);
    }

    public function test_photo_number_belongs_only_to_photo_remarks(): void
    {
        $product = $this->productWithReasons([
            ['field' => 'name', 'image_index' => 1, 'evidence' => 'Название не подходит.'],
        ]);

        $issues = app(ProductModerationIssuePresenter::class)->forProduct($product);

        $this->assertNull($issues[0]['image_index']);
        $this->assertSame([], $issues[0]['images']);
    }

    public function test_one_remark_about_two_photos_keeps_both_thumbnails(): void
    {
        $product = $this->productWithReasons([
            ['field' => 'images', 'image_index' => 1, 'evidence' => 'На фото водяной знак.'],
            ['field' => 'images', 'image_index' => 2, 'evidence' => 'На фото водяной знак.'],
        ], images: 2);

        $issues = app(ProductModerationIssuePresenter::class)->forProduct($product);

        $this->assertCount(1, $issues);
        $this->assertSame([1, 2], array_column($issues[0]['images'], 'index'));
    }
}
