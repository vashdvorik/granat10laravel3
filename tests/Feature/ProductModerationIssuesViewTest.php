<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Блок статуса товара: состояние и замечания проверки живут в одном блоке. */
class ProductModerationIssuesViewTest extends TestCase
{
    use RefreshDatabase;

    private function changesRequired(array $overrides = []): Product
    {
        return Product::factory()->make(array_merge([
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'moderation_summary' => null,
            'is_active' => false,
            'publication_requested' => true,
        ], $overrides));
    }

    /** @return array<string, mixed> */
    private function issue(array $overrides = []): array
    {
        return array_merge([
            'field' => 'description',
            'label' => 'Описание',
            'target' => 'desc',
            'message' => 'Удалите контактные данные.',
            'suggestion' => 'Оставьте только описание товара.',
            'fields' => [['field' => 'description', 'label' => 'Описание', 'target' => 'desc']],
            'images' => [],
        ], $overrides);
    }

    public function test_issue_is_listed_inside_the_status_block_and_links_to_the_form_field(): void
    {
        $html = $this->blade(
            '<x-product-moderation-status :product="$product" :issues="$issues" />',
            ['product' => $this->changesRequired(), 'issues' => [$this->issue()]]
        );

        $html->assertSee('product-status-box__issue', false);
        $html->assertSee('href="#desc"', false);
        $html->assertSee('Удалите контактные данные.');
        $html->assertSee('Что сделать:');
        $html->assertSee('Оставьте только описание товара.');
        // всё в одном блоке: отдельного списка «Что нужно исправить» больше нет
        $html->assertDontSee('Что нужно исправить');
        $html->assertDontSee('product-moderation-issues', false);
    }

    public function test_links_can_lead_to_the_edit_form_from_the_product_page(): void
    {
        $html = $this->blade(
            '<x-product-moderation-status :product="$product" :issues="$issues" field-link-base="/account/store/products/7/edit" />',
            ['product' => $this->changesRequired(), 'issues' => [$this->issue()]]
        );

        $html->assertSee('href="/account/store/products/7/edit#desc"', false);
    }

    public function test_summary_is_not_repeated_when_detailed_issues_are_present(): void
    {
        $html = $this->blade(
            '<x-product-moderation-status :product="$product" :issues="$issues" />',
            ['product' => $this->changesRequired(['moderation_summary' => 'Причина уже показана ниже.']), 'issues' => [$this->issue()]]
        );

        $html->assertDontSee('Причина уже показана ниже.');
    }

    public function test_summary_is_shown_without_detailed_issues_and_without_an_echo_title(): void
    {
        $html = $this->blade(
            '<x-product-moderation-status :product="$product" />',
            ['product' => $this->changesRequired(['moderation_summary' => 'Нужно исправить название.'])]
        );

        $html->assertSee('Нужно исправить название.');
        $html->assertDontSee('Что нужно исправить');
    }

    public function test_returned_product_without_any_remark_says_so_instead_of_pointing_to_nothing(): void
    {
        $html = $this->blade(
            '<x-product-moderation-status :product="$product" />',
            ['product' => $this->changesRequired()]
        );

        $html->assertSee('Подробных замечаний нет.');
        $html->assertSee('Проверьте название, описание, характеристики, категорию или фото');
    }

    public function test_rejected_product_keeps_a_titled_reason(): void
    {
        $html = $this->blade(
            '<x-product-moderation-status :product="$product" />',
            ['product' => $this->changesRequired([
                'moderation_status' => ProductModerationStatus::Rejected,
                'moderation_summary' => 'Товар запрещён к продаже.',
            ])]
        );

        $html->assertSee('Причина отказа:');
        $html->assertSee('Товар запрещён к продаже.');
    }

    public function test_image_issue_shows_thumbnail_and_photo_number(): void
    {
        $html = $this->blade(
            '<x-product-moderation-status :product="$product" :issues="$issues" />',
            ['product' => $this->changesRequired(), 'issues' => [$this->issue([
                'field' => 'images',
                'label' => 'Фотографии',
                'target' => 'product-images-section',
                'message' => 'Фото не относится к товару.',
                'suggestion' => 'Замените фото товара.',
                'fields' => [['field' => 'images', 'label' => 'Фотографии', 'target' => 'product-images-section']],
                'images' => [['index' => 1, 'url' => '/storage/products/problem.jpg']],
            ])]]
        );

        $html->assertSee('фото №1');
        $html->assertSee('src="/storage/products/problem.jpg"', false);
        $html->assertSee('Проблемное фото товара №1');
        $html->assertSee('Фото не относится к товару.');
    }

    public function test_merged_issue_names_every_field_in_one_item(): void
    {
        $html = $this->blade(
            '<x-product-moderation-status :product="$product" :issues="$issues" />',
            ['product' => $this->changesRequired(), 'issues' => [$this->issue([
                'field' => 'images',
                'label' => 'Фотографии',
                'target' => 'product-images-section',
                'message' => 'На фото холодильник, а в названии наушники.',
                'fields' => [
                    ['field' => 'images', 'label' => 'Фотографии', 'target' => 'product-images-section'],
                    ['field' => 'name', 'label' => 'Название', 'target' => 'name'],
                ],
                'images' => [['index' => 1, 'url' => '/storage/products/p.jpg']],
            ])]]
        );

        $this->assertSame(1, substr_count((string) $html, 'class="product-status-box__issue"'));
        $this->assertSame(1, substr_count((string) $html, 'На фото холодильник, а в названии наушники.'));
        $html->assertSee('Фотографии</a>, <a href="#name">Название</a>', false);
    }
}
