<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModerationIssuesViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_overview_links_to_form_field(): void
    {
        $html = $this->blade(
            '<x-product-moderation-issues :issues="$issues" />',
            ['issues' => [[
                'field' => 'description',
                'label' => 'Описание',
                'target' => 'desc',
                'message' => 'Удалите контактные данные.',
                'suggestion' => 'Оставьте только описание товара.',
            ]]]
        );

        $html->assertSee('href="#desc"', false);
        $html->assertSee('product-moderation-issues__item', false);
        $html->assertSee('Удалите контактные данные.');
        $html->assertSee('Оставьте только описание товара.');
    }

    public function test_status_component_hides_summary_when_detailed_issues_are_present(): void
    {
        $product = Product::factory()->make([
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'moderation_summary' => 'Причина уже показана ниже.',
            'is_active' => false,
            'publication_requested' => true,
        ]);

        $html = $this->blade(
            '<x-product-moderation-status :product="$product" :has-issues="true" />',
            ['product' => $product]
        );

        $html->assertDontSee('Причина уже показана ниже.');
    }

    public function test_status_component_shows_summary_without_detailed_issues(): void
    {
        $product = Product::factory()->make([
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'moderation_summary' => 'Нужно исправить название.',
            'is_active' => false,
            'publication_requested' => true,
        ]);

        $html = $this->blade(
            '<x-product-moderation-status :product="$product" />',
            ['product' => $product]
        );

        $html->assertSee('Нужно исправить название.');
    }

    public function test_inline_component_shows_only_requested_field(): void
    {
        $html = $this->blade(
            '<x-product-moderation-field-issues :issues="$issues" field="name" />',
            ['issues' => [
                [
                    'field' => 'name',
                    'message' => 'Исправьте название.',
                    'suggestion' => null,
                ],
                [
                    'field' => 'images',
                    'message' => 'Замените фотографию.',
                    'suggestion' => null,
                ],
            ]]
        );

        $html->assertSee('Исправьте название.');
        $html->assertDontSee('Замените фотографию.');
    }

    public function test_issue_overview_shows_image_preview_when_available(): void
    {
        $html = $this->blade(
            '<x-product-moderation-issues :issues="$issues" />',
            ['issues' => [[
                'field' => 'images',
                'label' => 'Фотографии',
                'target' => 'product-images-section',
                'message' => 'Фото не относится к товару.',
                'suggestion' => 'Замените фото товара.',
                'is_image_issue' => true,
                'image_index' => 1,
                'image_url' => '/storage/products/problem.jpg',
            ]]]
        );

        $html->assertSee('Фото №1');
        $html->assertSee('src="/storage/products/problem.jpg"', false);
        $html->assertSee('Проблемное фото товара №1');
        $html->assertSee('Фото не относится к товару.');
    }
}
