<?php

namespace Tests\Feature;

use App\Application\Products\UpdateProduct;
use App\Contracts\AiModerationClient;
use App\Data\ProductModerationPayload;
use App\Data\ProductModerationResult;
use App\Enums\ProductModerationStatus;
use App\Enums\ProductSaveAction;
use App\Events\ProductModerationDecided;
use App\Jobs\ModerateProductJob;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Models\Seller;
use App\Notifications\ProductModerationDecisionNotification;
use App\Services\ProductAutoApprovalPolicy;
use App\Services\ProductModerationPayloadFactory;
use App\Services\ProductStatusStoryFactory;
use App\Services\ProductWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Модерация глазами новичка: честные статусы и сообщения, отправка не «теряется», итог автопроверки доходит до продавца. */
class ProductModerationUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('moderation.ai_enabled', false);
    }

    /** @param  array<string, mixed>  $overrides */
    private function product(array $overrides = [], bool $withImage = true): Product
    {
        $seller = Seller::factory()->create();
        $product = Product::factory()->create(array_merge([
            'seller_id' => $seller->id,
            'category_id' => Category::factory()->create()->id,
            'desc' => 'Подробное описание товара для проверки модератором.',
        ], $overrides));

        if ($withImage) {
            Image::factory()->main()->create(['product_id' => $product->id]);
        }

        return $product->refresh();
    }

    /** Форма редактирования без изменений в проверяемых полях. */
    private function form(Product $product, ProductSaveAction $action, array $override = []): array
    {
        return array_merge([
            'name' => $product->name,
            'price' => $product->price,
            'lowprice' => $product->lowprice,
            'desc' => $product->desc,
            'features1' => $product->features1,
            'features2' => $product->features2,
            'features3' => $product->features3,
            'features4' => $product->features4,
            'category_id' => $product->category_id,
            'can_order' => $product->can_order,
            'delivery_enabled' => $product->delivery_enabled,
            'save_action' => $action->value,
            'expected_moderation_version' => $product->moderation_version,
        ], $override);
    }

    private function update(Product $product, ProductSaveAction $action, array $override = []): \App\Data\ProductWriteResult
    {
        return app(UpdateProduct::class)->execute($product, $product->seller->user, $this->form($product, $action, $override));
    }

    // ------------------------------------------------------------------ отправка на проверку не «теряется»

    public function test_draft_is_submitted_even_when_nothing_moderated_was_changed(): void
    {
        $product = $this->product(['moderation_status' => ProductModerationStatus::Draft, 'is_active' => false,
            'publication_requested' => false, 'moderated_version' => null]);

        $result = $this->update($product, ProductSaveAction::Submit);

        $this->assertSame(ProductModerationStatus::Pending, $result->product->moderation_status);
        $this->assertTrue($result->moderationDispatched);
        $this->assertTrue($result->product->publication_requested);
        $this->assertStringContainsString('отправлен на проверку', $result->userMessage);
        $this->assertStringContainsString('колокольчик', $result->userMessage);
    }

    public function test_changes_required_product_is_resubmitted_after_editing_only_price(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::ChangesRequired, 'is_active' => false,
            'publication_requested' => false, 'moderated_version' => null,
            'moderation_summary' => 'Добавьте описание.',
        ]);

        $result = $this->update($product, ProductSaveAction::Submit, ['price' => $product->price + 50]);

        $this->assertSame(ProductModerationStatus::Pending, $result->product->moderation_status);
        $this->assertTrue($result->moderationDispatched);
    }

    public function test_rejected_product_cannot_be_resubmitted_without_changes_and_nothing_is_saved(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Rejected, 'is_active' => false,
            'publication_requested' => false, 'moderated_version' => null,
            'moderation_summary' => 'Запрещённый товар.', 'price' => 100,
        ]);

        try {
            $this->update($product, ProductSaveAction::Submit, ['price' => 999]);
            $this->fail('Ожидалась ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('сначала исправьте карточку', $exception->errors()['save_action'][0]);
        }

        $this->assertEquals(100, $product->fresh()->price);
        $this->assertSame(ProductModerationStatus::Rejected, $product->fresh()->moderation_status);
    }

    public function test_rejected_product_goes_to_review_after_real_edit(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Rejected, 'is_active' => false,
            'publication_requested' => false, 'moderated_version' => null, 'moderation_summary' => 'Исправьте.',
        ]);

        $result = $this->update($product, ProductSaveAction::Submit, ['name' => 'Исправленное название товара']);

        $this->assertSame(ProductModerationStatus::Pending, $result->product->moderation_status);
        $this->assertStringContainsString('отправлен на проверку', $result->userMessage);
    }

    // ------------------------------------------------------------------ сообщения говорят правду

    public function test_price_change_of_published_product_keeps_it_live_and_says_so(): void
    {
        $product = $this->product(['is_active' => true]);

        $result = $this->update($product, ProductSaveAction::Submit, ['price' => $product->price + 10]);

        $this->assertSame(ProductModerationStatus::Approved, $result->product->moderation_status);
        $this->assertTrue((bool) $result->product->is_active);
        $this->assertFalse($result->moderationDispatched);
        // раньше здесь было «сохранены и отправлены на проверку», хотя ничего не отправлялось
        $this->assertSame('Изменения сохранены и уже видны покупателям.', $result->userMessage);
    }

    public function test_content_change_of_published_product_warns_that_it_returns_to_review(): void
    {
        $product = $this->product(['is_active' => true]);

        $result = $this->update($product, ProductSaveAction::Submit, ['name' => 'Новое название товара']);

        $this->assertSame(ProductModerationStatus::Pending, $result->product->moderation_status);
        $this->assertFalse((bool) $result->product->is_active);
        $this->assertStringContainsString('отправлен на проверку', $result->userMessage);
        $this->assertStringContainsString('покупатели его не видят', $result->userMessage);
    }

    public function test_saving_draft_of_published_product_explains_it_was_hidden(): void
    {
        $product = $this->product(['is_active' => true]);

        $result = $this->update($product, ProductSaveAction::Draft, ['desc' => 'Совсем другое подробное описание товара.']);

        $this->assertSame(ProductModerationStatus::Draft, $result->product->moderation_status);
        $this->assertStringContainsString('скрыт из каталога', $result->userMessage);
        $this->assertStringContainsString('название, описание, характеристики, категорию или фото', $result->userMessage);
    }

    public function test_plain_save_with_moderated_change_explains_what_to_press(): void
    {
        $product = $this->product(['is_active' => true]);

        try {
            $this->update($product, ProductSaveAction::Save, ['name' => 'Новое название']);
            $this->fail('Ожидалась ValidationException.');
        } catch (ValidationException $exception) {
            $message = $exception->errors()['save_action'][0];
            $this->assertStringContainsString('такие правки проверяются заново', $message);
            $this->assertStringContainsString('Отправить на проверку', $message);
        }
    }

    public function test_item_already_in_review_says_it_is_still_in_review(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Pending, 'is_active' => false,
            'moderated_version' => null,
        ]);

        $result = $this->update($product, ProductSaveAction::Submit, ['price' => $product->price + 1]);

        $this->assertSame(ProductModerationStatus::Pending, $result->product->moderation_status);
        $this->assertFalse($result->moderationDispatched);
        $this->assertSame('Изменения сохранены. Товар по-прежнему на проверке.', $result->userMessage);
    }

    // ------------------------------------------------------------------ замечания не пропадают

    public function test_remark_survives_saving_draft_until_resubmitted(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::ChangesRequired, 'is_active' => false,
            'publication_requested' => false, 'moderated_version' => null,
            'moderation_summary' => 'Добавьте в описание размеры и материал.',
        ]);

        $draft = $this->update($product, ProductSaveAction::Draft, ['name' => 'Частично исправленное название'])->product;

        $this->assertSame(ProductModerationStatus::Draft, $draft->moderation_status);
        $this->assertSame('Добавьте в описание размеры и материал.', $draft->moderation_summary);

        $story = app(ProductStatusStoryFactory::class)->make($draft);
        $this->assertSame('draft', $story->key);
        $this->assertSame('Замечания прошлой проверки', $story->remarkTitle);
        $this->assertSame('Добавьте в описание размеры и материал.', $story->remark);
    }

    public function test_remark_of_approved_product_is_not_kept_after_content_change(): void
    {
        $product = $this->product(['is_active' => true, 'moderation_summary' => 'Одобрено с комментарием']);

        $draft = $this->update($product, ProductSaveAction::Draft, ['name' => 'Другое название'])->product;

        $this->assertNull($draft->moderation_summary);
    }

    // ------------------------------------------------------------------ служебные тексты не доходят до продавца

    public function test_internal_review_reason_is_not_shown_as_a_remark(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::NeedsReview, 'is_active' => false,
            'moderated_version' => null,
            'moderation_summary' => 'Оценка риска выше порога автоматической публикации.',
        ]);

        $story = app(ProductStatusStoryFactory::class)->make($product);

        $this->assertSame('manual_review', $story->key);
        $this->assertNull($story->remark);
        $this->assertStringNotContainsString('порога', $story->explanation.$story->nextStep);
        $this->assertStringContainsString('Это не отказ', $story->explanation);
    }

    // ------------------------------------------------------------------ итог автопроверки доходит до продавца

    private function runAiJob(Product $product, ProductModerationResult $result): void
    {
        config()->set('moderation.mode', 'auto');
        config()->set('moderation.auto_approve.excluded_category_aliases', []);
        ProductModerationRun::query()->create([
            'product_id' => $product->id, 'content_version' => $product->moderation_version,
            'trigger' => 'seller_submit', 'provider' => 'local', 'status' => 'completed', 'decision' => 'allow',
            'risk_score' => 0, 'request_fingerprint' => hash('sha256', 'local-'.$product->id),
        ]);

        $client = new class($result) implements AiModerationClient
        {
            public function __construct(private readonly ProductModerationResult $result) {}

            public function moderate(ProductModerationPayload $payload): ProductModerationResult
            {
                return $this->result;
            }
        };

        (new ModerateProductJob($product->id, $product->moderation_version))->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class),
        );
    }

    public function test_auto_approval_notifies_seller(): void
    {
        Event::fake([ProductModerationDecided::class]);
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);

        $this->runAiJob($product, new ProductModerationResult(decision: 'allow', riskScore: 0.01));

        $this->assertSame(ProductModerationStatus::Approved, $product->fresh()->moderation_status);
        Event::assertDispatched(ProductModerationDecided::class, fn ($e): bool => $e->productId === $product->id);
    }

    public function test_ai_remarks_notify_seller(): void
    {
        Event::fake([ProductModerationDecided::class]);
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);

        $this->runAiJob($product, new ProductModerationResult('review', 0.8, [[
            'code' => 'profanity', 'confidence' => 0.98, 'evidence' => 'В названии обнаружена ненормативная лексика.',
        ]]));

        $this->assertSame(ProductModerationStatus::ChangesRequired, $product->fresh()->moderation_status);
        Event::assertDispatched(ProductModerationDecided::class, fn ($e): bool => $e->productId === $product->id);
    }

    public function test_handing_over_to_administrator_does_not_send_a_notification(): void
    {
        Event::fake([ProductModerationDecided::class]);
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);

        // review без разбора по существу: остаётся только передать администратору
        $this->runAiJob($product, new ProductModerationResult('review', 0.8, []));

        $this->assertSame(ProductModerationStatus::NeedsReview, $product->fresh()->moderation_status);
        Event::assertNotDispatched(ProductModerationDecided::class);
    }

    public function test_shadow_mode_never_notifies_seller(): void
    {
        Event::fake([ProductModerationDecided::class]);
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        config()->set('moderation.mode', 'shadow');

        $client = new class implements AiModerationClient
        {
            public function moderate(ProductModerationPayload $payload): ProductModerationResult
            {
                return new ProductModerationResult(decision: 'allow', riskScore: 0.01);
            }
        };

        (new ModerateProductJob($product->id, $product->moderation_version, mode: 'shadow'))->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class),
        );

        $this->assertSame(ProductModerationStatus::Pending, $product->fresh()->moderation_status);
        Event::assertNotDispatched(ProductModerationDecided::class);
    }

    // ------------------------------------------------------------------ текст уведомления

    public function test_notification_for_published_product_says_what_happened(): void
    {
        $product = $this->product(['is_active' => true]);

        $data = (new ProductModerationDecisionNotification($product))->toArray($product->seller->user);

        $this->assertSame('Товар прошёл проверку и опубликован', $data['title']);
        $this->assertNull($data['comment']);
        $this->assertSame(route('account.store.products.show', $product), $data['url']);
    }

    public function test_notification_for_remarks_carries_the_remark_text(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::ChangesRequired, 'is_active' => false,
            'moderated_version' => null, 'moderation_summary' => 'Уберите телефон из названия.',
        ]);

        $data = (new ProductModerationDecisionNotification($product))->toArray($product->seller->user);

        $this->assertSame('Нужно исправить товар и отправить на проверку снова', $data['title']);
        $this->assertSame('Уберите телефон из названия.', $data['comment']);
    }

    public function test_notification_for_rejection_carries_the_reason(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Rejected, 'is_active' => false,
            'moderated_version' => null, 'moderation_summary' => 'Запрещённый к продаже товар.',
        ]);

        $data = (new ProductModerationDecisionNotification($product))->toArray($product->seller->user);

        $this->assertSame('Товар отклонён', $data['title']);
        $this->assertSame('Запрещённый к продаже товар.', $data['comment']);
    }

    public function test_admin_navigation_badge_counts_products_waiting_for_a_human_decision(): void
    {
        $this->assertNull(\App\Filament\Resources\ProductResource::getNavigationBadge());

        $this->product(['moderation_status' => ProductModerationStatus::Pending, 'is_active' => false, 'moderated_version' => null]);
        $this->product(['moderation_status' => ProductModerationStatus::NeedsReview, 'is_active' => false, 'moderated_version' => null]);
        $this->product(['moderation_status' => ProductModerationStatus::Failed, 'is_active' => false, 'moderated_version' => null]);
        // не ждут человека: ИИ ещё работает, это черновик, это уже одобрено
        $this->product(['moderation_status' => ProductModerationStatus::Processing, 'is_active' => false, 'moderated_version' => null]);
        $this->product(['moderation_status' => ProductModerationStatus::Draft, 'is_active' => false, 'moderated_version' => null]);
        $this->product(['is_active' => true]);

        $this->assertSame('3', \App\Filament\Resources\ProductResource::getNavigationBadge());
    }

    public function test_write_service_update_still_returns_product_for_existing_callers(): void
    {
        $product = $this->product(['moderation_status' => ProductModerationStatus::Draft, 'is_active' => false,
            'publication_requested' => false, 'moderated_version' => null]);

        $updated = app(ProductWriteService::class)->update(
            $product,
            $this->form($product, ProductSaveAction::Draft, ['price' => $product->price + 5]),
        );

        $this->assertInstanceOf(Product::class, $updated);
    }
}
