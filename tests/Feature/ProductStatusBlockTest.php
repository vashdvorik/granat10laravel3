<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Models\Seller;
use App\Models\User;
use App\Services\CabinetDesignManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Страница товара в кабинете: состояние и замечания проверки — один блок, и ни одна мысль в нём не повторяется.
 */
class ProductStatusBlockTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Seller $seller;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('public');
        config()->set('moderation.ai_enabled', false);
        $this->user = User::factory()->create();
        $this->seller = Seller::factory()->create(['user_id' => $this->user->id]);
    }

    /** @return array<string, array{0: string}> */
    public static function cabinetThemes(): array
    {
        return ['legacy' => ['legacy'], 'new' => ['new']];
    }

    /** @param  array<string, mixed>  $overrides */
    private function product(array $overrides = [], bool $withImage = true): Product
    {
        $product = Product::factory()->create(array_merge([
            'seller_id' => $this->seller->id,
            'category_id' => Category::factory()->create()->id,
            'moderation_version' => 4,
        ], $overrides));

        if ($withImage) {
            $image = Image::factory()->main()->create(['product_id' => $product->id]);
            Storage::disk('public')->put($image->path, 'x');
        }

        return $product->refresh();
    }

    /** @param  array<int, array<string, mixed>>  $reasons */
    private function withIssues(Product $product, array $reasons): Product
    {
        ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => $product->moderation_version,
            'trigger' => 'queue',
            'provider' => 'test',
            'status' => 'completed',
            'request_fingerprint' => hash('sha256', uniqid('', true)),
            'reasons' => $reasons,
        ]);

        return $product;
    }

    /** Текст блока статуса: то, что видит продавец. */
    private function blockText(string $html): string
    {
        $this->assertSame(1, substr_count($html, 'id="product-status-box"'), 'Блок статуса должен быть на странице один');

        $start = strpos($html, '<div class="product-status-box ');
        $end = strpos($html, '<!-- Start: Product Price -->', $start);
        $block = substr($html, $start, ($end === false ? 6000 : $end - $start));

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($block), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /** @return array<int, string> */
    private function sentences(string $text): array
    {
        return array_values(array_filter(array_map(
            fn (string $sentence): string => mb_strtolower(trim($sentence, " \t\n\r.,;:!?—«»\"'")),
            preg_split('/(?<=[.!?])\s+/u', $text) ?: []
        ), fn (string $sentence): bool => mb_strlen($sentence) > 12));
    }

    // ------------------------------------------------------------------ сценарий из жалобы

    /** @dataProvider cabinetThemes */
    public function test_repeated_remark_is_shown_once_inside_one_block(string $theme): void
    {
        app(CabinetDesignManager::class)->setActive($theme);

        $text = 'На изображении представлен мини-холодильник, в то время как в названии указаны наушники Apple AirPods.';
        $tip = 'Приведите название и категорию в соответствие с реальным товаром на фото.';
        $product = $this->withIssues($this->product([
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'is_active' => false, 'moderated_version' => null, 'publication_requested' => true,
        ]), [
            ['field' => 'images', 'image_index' => 1, 'evidence' => $text, 'suggestion' => $tip],
            ['field' => 'name', 'image_index' => 1, 'evidence' => $text, 'suggestion' => $tip],
        ]);

        $html = $this->actingAs($this->user)->get(route('account.store.products.show', $product))
            ->assertOk()
            ->assertSee('Нужно исправить')
            ->assertDontSee('Что нужно исправить')
            ->assertDontSee('product-moderation-issues', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, $text), 'Замечание должно быть показано один раз');
        $this->assertSame(1, substr_count($html, $tip), 'Рекомендация должна быть показана один раз');
        $this->assertSame(1, substr_count($html, 'class="product-status-box__issue"'));
        $this->assertSame(1, substr_count($html, 'Нужно исправить</strong>'), 'Заголовок «Нужно исправить» — один');
        $this->assertStringContainsString('Фотографии</a>, <a href="'.route('account.store.products.edit', $product).'#name">Название</a>', $html);
        $this->assertStringContainsString('фото №1', $html);
    }

    public function test_edit_form_has_the_same_single_block_and_no_repeats_next_to_fields(): void
    {
        $product = $this->withIssues($this->product([
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'is_active' => false, 'moderated_version' => null, 'publication_requested' => true,
        ]), [
            ['field' => 'name', 'evidence' => 'Уберите телефон из названия.', 'suggestion' => 'Оставьте только название.'],
            ['field' => 'description', 'evidence' => 'Добавьте размеры в описание.'],
        ]);

        $html = $this->actingAs($this->user)->get(route('account.store.products.edit', $product))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, 'id="product-status-box"'));
        $this->assertSame(1, substr_count($html, 'Уберите телефон из названия.'));
        $this->assertSame(1, substr_count($html, 'Добавьте размеры в описание.'));
        $this->assertStringNotContainsString('product-moderation-field-issue', $html);
        // на форме ссылки ведут к полям той же страницы
        $this->assertStringContainsString('<a href="#name">Название</a>', $html);
        $this->assertStringContainsString('<a href="#desc">Описание</a>', $html);
    }

    // ------------------------------------------------------------------ все состояния

    /** @return array<string, array{0: string, 1: array<string, mixed>, 2: bool}> */
    public static function states(): array
    {
        $none = ['is_active' => false, 'publication_requested' => false, 'moderated_version' => null];

        return [
            'draft' => ['draft', array_merge($none, ['moderation_status' => ProductModerationStatus::Draft]), true],
            'draft with remark' => ['draft', array_merge($none, ['moderation_status' => ProductModerationStatus::Draft, 'moderation_summary' => 'Добавьте размеры в описание.']), true],
            'queued' => ['queued', array_merge($none, ['moderation_status' => ProductModerationStatus::Pending, 'publication_requested' => true]), true],
            'checking' => ['checking', array_merge($none, ['moderation_status' => ProductModerationStatus::Processing, 'publication_requested' => true]), true],
            'needs review' => ['manual_review', array_merge($none, ['moderation_status' => ProductModerationStatus::NeedsReview, 'publication_requested' => true]), true],
            'failed' => ['manual_review', array_merge($none, ['moderation_status' => ProductModerationStatus::Failed, 'publication_requested' => true]), true],
            'changes required' => ['changes_required', array_merge($none, ['moderation_status' => ProductModerationStatus::ChangesRequired, 'publication_requested' => true, 'moderation_summary' => 'Уберите телефон из названия.']), true],
            'changes required without details' => ['changes_required', array_merge($none, ['moderation_status' => ProductModerationStatus::ChangesRequired, 'publication_requested' => true]), true],
            'rejected' => ['rejected', array_merge($none, ['moderation_status' => ProductModerationStatus::Rejected, 'moderation_summary' => 'Товар запрещён к продаже.']), true],
            'approved hidden' => ['approved_hidden', ['moderation_status' => ProductModerationStatus::Approved, 'is_active' => false, 'moderated_version' => 4], true],
            'approved outdated' => ['outdated', ['moderation_status' => ProductModerationStatus::Approved, 'is_active' => true, 'moderated_version' => 3], true],
            'approved without photo' => ['no_images', ['moderation_status' => ProductModerationStatus::Approved, 'is_active' => true, 'moderated_version' => 4], false],
            'published' => ['published', ['moderation_status' => ProductModerationStatus::Approved, 'is_active' => true, 'moderated_version' => 4], true],
        ];
    }

    /**
     * @dataProvider states
     *
     * @param  array<string, mixed>  $attributes
     */
    public function test_no_state_repeats_a_sentence_or_shows_a_second_block(string $key, array $attributes, bool $withImage): void
    {
        $product = $this->product($attributes, $withImage);

        $this->assertSame($key, $product->view_state->story->key);

        $html = $this->actingAs($this->user)->get(route('account.store.products.show', $product))
            ->assertOk()
            ->getContent();

        $text = $this->blockText($html);
        $this->assertNotSame('', $text);

        $sentences = $this->sentences($text);
        $this->assertSame(
            $sentences,
            array_values(array_unique($sentences)),
            "Блок состояния «{$key}» повторяет фразу: {$text}"
        );
        $this->assertStringNotContainsString('product-moderation-issues', $html);
    }

    public function test_every_block_is_short_enough_to_read_at_a_glance(): void
    {
        foreach (self::states() as $name => [$key, $attributes, $withImage]) {
            $product = $this->product($attributes, $withImage);
            $html = $this->actingAs($this->user)->get(route('account.store.products.show', $product))->getContent();

            $this->assertLessThanOrEqual(
                330,
                mb_strlen($this->blockText($html)),
                "Блок состояния «{$name}» слишком длинный: ".$this->blockText($html)
            );
        }
    }

    public function test_image_delete_response_returns_one_refreshed_block(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'is_active' => false, 'moderated_version' => null, 'publication_requested' => true,
        ]);
        $extra = Image::factory()->create(['product_id' => $product->id]);
        Storage::disk('public')->put($extra->path, 'x');
        $this->withIssues($product, [['field' => 'name', 'evidence' => 'Уберите телефон из названия.']]);

        $response = $this->actingAs($this->user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->deleteJson(route('account.store.products.images.destroy', $extra))
            ->assertOk()
            ->assertJsonStructure(['status_html']);

        $this->assertSame(1, substr_count($response->json('status_html'), 'id="product-status-box"'));
        $this->assertStringNotContainsString('product-moderation-issues', $response->json('status_html'));
    }
}
