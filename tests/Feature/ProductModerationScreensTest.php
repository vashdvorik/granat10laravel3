<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Seller;
use App\Models\User;
use App\Notifications\ProductModerationDecisionNotification;
use App\Services\CabinetDesignManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Экраны продавца: статус объяснён словами, кнопки называются честно, подсказки стоят там, где нужны. */
class ProductModerationScreensTest extends TestCase
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

    /** @param  array<string, mixed>  $overrides */
    private function product(array $overrides = [], bool $withImage = true): Product
    {
        $product = Product::factory()->create(array_merge([
            'seller_id' => $this->seller->id,
            'category_id' => Category::factory()->create()->id,
        ], $overrides));

        if ($withImage) {
            $image = Image::factory()->main()->create(['product_id' => $product->id]);
            Storage::disk('public')->put($image->path, 'x');
        }

        return $product->refresh();
    }

    private function draft(array $overrides = []): Product
    {
        return $this->product(array_merge([
            'moderation_status' => ProductModerationStatus::Draft, 'is_active' => false,
            'publication_requested' => false, 'moderated_version' => null,
        ], $overrides));
    }

    public static function cabinetThemes(): array
    {
        return ['legacy' => ['legacy'], 'new' => ['new']];
    }

    // ------------------------------------------------------------------ страница товара

    /** @dataProvider cabinetThemes */
    public function test_draft_page_explains_state_and_offers_honest_button(string $theme): void
    {
        app(CabinetDesignManager::class)->setActive($theme);
        $product = $this->draft();

        $html = $this->actingAs($this->user)->get(route('account.store.products.show', $product))
            ->assertOk()
            ->assertSee('Черновик')
            ->assertSee('Покупатели этот товар не видят')
            ->assertSee('Отправить на проверку')
            ->assertSee('Отправить товар на проверку?')
            ->assertDontSee('Модерация:')
            ->assertDontSee('Публикация:')
            ->getContent();

        // кнопка черновика больше не называется «Опубликовать»
        $this->assertStringNotContainsString('<i class="uil uil-shield-check"></i> Опубликовать', $html);
    }

    public function test_page_of_product_in_review_hides_internal_reason_and_tells_not_to_worry(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::NeedsReview, 'is_active' => false,
            'moderated_version' => null,
            'moderation_summary' => 'Оценка риска выше порога автоматической публикации.',
        ]);

        $this->actingAs($this->user)->get(route('account.store.products.show', $product))
            ->assertOk()
            ->assertSee('Проверяет администратор')
            ->assertSee('Это не отказ')
            ->assertSee('колокольчик')
            ->assertDontSee('порога');
    }

    public function test_page_shows_remark_and_next_step_when_changes_are_required(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::ChangesRequired, 'is_active' => false,
            'moderated_version' => null, 'moderation_summary' => 'Уберите телефон из названия.',
        ]);

        $this->actingAs($this->user)->get(route('account.store.products.show', $product))
            ->assertOk()
            ->assertSee('Нужно исправить')
            ->assertSee('Что нужно исправить')
            ->assertSee('Уберите телефон из названия.')
            ->assertSee('Исправьте замечания и нажмите');
    }

    public function test_page_of_published_product_says_it_is_visible(): void
    {
        $product = $this->product(['is_active' => true]);

        $this->actingAs($this->user)->get(route('account.store.products.show', $product))
            ->assertOk()
            ->assertSee('Опубликован')
            ->assertSee('Товар виден покупателям в каталоге и поиске.');
    }

    // ------------------------------------------------------------------ форма редактирования

    /** @dataProvider cabinetThemes */
    public function test_edit_form_of_draft_has_both_buttons_and_no_recheck_warning(string $theme): void
    {
        app(CabinetDesignManager::class)->setActive($theme);
        $product = $this->draft();

        $html = $this->actingAs($this->user)->get(route('account.store.products.edit', $product))
            ->assertOk()
            ->assertSee('data-recheck-mode="draft"', false)
            ->assertSee('Сохранить черновик')
            ->assertSee('Отправить на проверку')
            ->assertSee('ничего никуда не отправляет')
            ->assertDontSee('Что можно менять без проверки')
            ->getContent();

        $this->assertStringNotContainsString('<i class="uil uil-shield-check"></i> Опубликовать', $html);
    }

    /** @dataProvider cabinetThemes */
    public function test_edit_form_of_published_product_warns_before_hiding_it(string $theme): void
    {
        app(CabinetDesignManager::class)->setActive($theme);
        $product = $this->product(['is_active' => true]);

        $this->actingAs($this->user)->get(route('account.store.products.edit', $product))
            ->assertOk()
            ->assertSee('data-recheck-mode="live"', false)
            ->assertSee('Что можно менять без проверки')
            ->assertSee('название, описание, характеристики, категорию или фото')
            ->assertSee('Сохранить изменения')
            ->assertSee('Товар пропадёт из каталога') // диалог подтверждения
            ->assertDontSee('Сохранить черновик');
    }

    public function test_edit_form_of_product_in_review_warns_that_check_restarts(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Pending, 'is_active' => false, 'moderated_version' => null,
        ]);

        $this->actingAs($this->user)->get(route('account.store.products.edit', $product))
            ->assertOk()
            ->assertSee('data-recheck-mode="review"', false)
            ->assertSee('Ждёт проверки')
            ->assertSee('Что можно менять свободно')
            ->assertSee('проверка начнётся заново')
            ->assertDontSee('Сохранить черновик');
    }

    public function test_create_form_names_buttons_honestly(): void
    {
        $html = $this->actingAs($this->user)->get(route('account.store.products.create'))
            ->assertOk()
            ->assertSee('Отправить на проверку')
            ->assertSee('Сохранить черновик')
            ->assertSee('покупатели его не увидят')
            ->getContent();

        $this->assertStringNotContainsString('<i class="uil uil-shield-check"></i> Опубликовать', $html);
    }

    // ------------------------------------------------------------------ список

    /** @dataProvider cabinetThemes */
    public function test_list_has_help_and_clear_filter_names(string $theme): void
    {
        app(CabinetDesignManager::class)->setActive($theme);
        $this->draft(['name' => 'Мой черновик']);

        $this->actingAs($this->user)->get(route('account.store.products.index'))
            ->assertOk()
            ->assertSee('Как проходит проверка товара')
            ->assertSee('Опубликованы')
            ->assertSee('Не опубликованы')
            ->assertSee('Отправить товар на проверку?')
            ->assertSee('Снять товар с публикации?')
            ->assertDontSee('Скрыть товар?');
    }

    // ------------------------------------------------------------------ удаление фото и отправка

    public function test_deleting_a_photo_of_published_product_tells_it_became_a_draft(): void
    {
        $product = $this->product(['is_active' => true]);
        $extra = Image::factory()->create(['product_id' => $product->id]);
        Storage::disk('public')->put($extra->path, 'x');

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->delete(route('account.store.products.images.destroy', $extra));

        $response->assertOk()->assertJson(['success' => true, 'state_changed' => true]);
        $this->assertStringContainsString('скрыт из каталога', $response->json('message'));
        $this->assertStringContainsString('Черновик', $response->json('status_html'));
        $this->assertSame(ProductModerationStatus::Draft, $product->fresh()->moderation_status);
    }

    public function test_deleting_a_photo_of_draft_gives_plain_message(): void
    {
        $product = $this->draft();
        $extra = Image::factory()->create(['product_id' => $product->id]);
        Storage::disk('public')->put($extra->path, 'x');

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->delete(route('account.store.products.images.destroy', $extra));

        $response->assertOk()->assertJson(['message' => 'Фото удалено.', 'state_changed' => false]);
    }

    public function test_submit_button_answer_mentions_notification_bell(): void
    {
        $product = $this->draft();

        $response = $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('account.store.products.submit-moderation', $product));

        $response->assertOk();
        $this->assertStringContainsString('колокольчик', $response->json('message'));
        $this->assertStringContainsString('покупатели его не видят', $response->json('message'));
    }

    public function test_second_submit_from_another_tab_is_blocked_and_pages_explain_the_error(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Pending, 'is_active' => false, 'moderated_version' => null,
        ]);

        $this->actingAs($this->user)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post(route('account.store.products.submit-moderation', $product))
            ->assertForbidden();

        // страницы товара и списка переводят 403 в понятную подсказку
        $this->actingAs($this->user)->get(route('account.store.products.show', $product))
            ->assertSee('статус товара уже изменился', false);
        $this->actingAs($this->user)->get(route('account.store.products.index'))
            ->assertSee('статус товара уже изменился', false);
    }

    // ------------------------------------------------------------------ колокольчик

    /** @dataProvider cabinetThemes */
    public function test_bell_shows_outcome_and_remark(string $theme): void
    {
        app(CabinetDesignManager::class)->setActive($theme);
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::ChangesRequired, 'is_active' => false,
            'moderated_version' => null, 'moderation_summary' => 'Добавьте размеры в описание.',
            'name' => 'Чайник для колокольчика',
        ]);
        $this->user->notify(new ProductModerationDecisionNotification($product));

        $this->actingAs($this->user)->get(route('account.index'))
            ->assertOk()
            ->assertSee('Чайник для колокольчика')
            ->assertSee('Нужно исправить товар и отправить на проверку снова')
            ->assertSee('Добавьте размеры в описание.');
    }
}
