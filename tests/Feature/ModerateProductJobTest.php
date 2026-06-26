<?php

namespace Tests\Feature;

use App\Contracts\AiModerationClient;
use App\Data\ProductModerationPayload;
use App\Data\ProductModerationResult;
use App\Enums\ProductModerationStatus;
use App\Exceptions\AiModerationProviderException;
use App\Jobs\ModerateProductImagesJob;
use App\Jobs\ModerateProductJob;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Services\ProductAutoApprovalPolicy;
use App\Services\ProductModerationPayloadFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class ModerateProductJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('moderation.mode', 'auto');
        config()->set('moderation.auto_approve.excluded_category_aliases', []);
    }

    public function test_allow_result_approves_current_product_version(): void
    {
        $product = Product::factory()->pendingModeration()->create([
            'is_active' => true,
            'moderation_version' => 2,
        ]);
        $this->allowAutoApprovalFor($product);
        $client = $this->clientReturning(new ProductModerationResult(
            decision: 'allow',
            riskScore: 0.05,
            providerRequestId: 'request-1'
        ));

        (new ModerateProductJob($product->id, 2))->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );

        $product->refresh();
        $this->assertSame(ProductModerationStatus::Approved, $product->moderation_status);
        $this->assertSame(2, $product->moderated_version);
        $this->assertTrue((bool) $product->is_active);
        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'provider' => 'fake',
            'model' => 'fake',
            'prompt_version' => 'product-moderation-v2',
            'decision' => 'allow',
            'status' => 'completed',
        ]);
        $run = ProductModerationRun::query()->where('product_id', $product->id)->latest('id')->firstOrFail();
        $this->assertSame('marketplace-policy-v1', $run->text_result['policy_version']);
    }

    public function test_allow_result_does_not_auto_approve_when_product_image_was_not_analyzed(): void
    {
        config()->set('moderation.provider', 'google');
        $product = Product::factory()->pendingModeration()->create([
            'is_active' => true,
            'moderation_version' => 2,
        ]);
        Image::factory()->for($product)->create();
        $this->allowAutoApprovalFor($product);
        $client = $this->clientReturning(new ProductModerationResult(
            decision: 'allow',
            riskScore: 0.05,
            imagesAnalyzed: false,
        ));

        (new ModerateProductJob($product->id, 2))->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );

        $product->refresh();

        $this->assertSame(ProductModerationStatus::NeedsReview, $product->moderation_status);
        $this->assertFalse((bool) $product->is_active);
        $this->assertStringContainsString('Изображения товара', $product->moderation_summary);
    }

    public function test_text_only_compatible_provider_does_not_auto_approve_product_with_unchecked_images(): void
    {
        config()->set('moderation.provider', 'openai_compatible');
        config()->set('moderation.openai_compatible.vision_enabled', false);
        $product = Product::factory()->pendingModeration()->create([
            'is_active' => true,
            'moderation_version' => 2,
        ]);
        Image::factory()->for($product)->create();
        $this->allowAutoApprovalFor($product);
        $client = $this->clientReturning(new ProductModerationResult(
            decision: 'allow',
            riskScore: 0.05,
            imagesAnalyzed: false,
        ));

        (new ModerateProductJob($product->id, 2))->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );

        $product->refresh();

        $this->assertSame(ProductModerationStatus::NeedsReview, $product->moderation_status);
        $this->assertFalse((bool) $product->is_active);
        $this->assertSame('Изображения товара не были проверены нейросетью.', $product->moderation_summary);
    }

    public function test_text_job_waits_for_separate_image_moderation_when_enabled(): void
    {
        Queue::fake();
        config()->set('moderation.images.enabled', true);
        $product = Product::factory()->pendingModeration()->create([
            'is_active' => true,
            'moderation_version' => 2,
        ]);
        Image::factory()->for($product)->create();
        $this->allowAutoApprovalFor($product);

        (new ModerateProductJob($product->id, 2))->handle(
            $this->clientReturning(new ProductModerationResult('allow', 0.01)),
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );

        $product->refresh();

        $this->assertSame(ProductModerationStatus::Processing, $product->moderation_status);
        $this->assertFalse((bool) $product->is_active);
        $this->assertNull($product->moderation_summary);
        Queue::assertPushed(
            ModerateProductImagesJob::class,
            fn (ModerateProductImagesJob $job): bool => $job->productId === $product->id
                && $job->contentVersion === 2
        );
    }

    public function test_block_result_requires_manual_review_instead_of_auto_rejection(): void
    {
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $client = $this->clientReturning(new ProductModerationResult(
            decision: 'block',
            riskScore: 0.95,
            violations: [['code' => 'weapons']],
            sellerMessage: 'Требуется ручная проверка.'
        ));

        (new ModerateProductJob($product->id, $product->moderation_version))->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );

        $product->refresh();
        $this->assertSame(ProductModerationStatus::NeedsReview, $product->moderation_status);
        $this->assertNull($product->moderated_version);
        $this->assertFalse((bool) $product->is_active);
    }

    public function test_stale_job_does_not_change_product(): void
    {
        $product = Product::factory()->pendingModeration()->create([
            'moderation_version' => 3,
        ]);
        $client = $this->clientReturning(new ProductModerationResult('allow', 0.01));

        (new ModerateProductJob($product->id, 2))->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );

        $this->assertSame(ProductModerationStatus::Pending, $product->fresh()->moderation_status);
        $this->assertDatabaseCount('product_moderation_runs', 0);
    }

    public function test_provider_failure_keeps_product_processing_until_queue_retries_finish(): void
    {
        config()->set('queue.default', 'database');
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $client = new class implements AiModerationClient
        {
            public function moderate(ProductModerationPayload $payload): ProductModerationResult
            {
                throw new RuntimeException('Provider unavailable');
            }
        };

        try {
            (new ModerateProductJob($product->id, $product->moderation_version))->handle(
                $client,
                app(ProductModerationPayloadFactory::class),
                app(ProductAutoApprovalPolicy::class)
            );
            $this->fail('RuntimeException was not thrown.');
        } catch (RuntimeException) {
            $this->assertSame(ProductModerationStatus::Processing, $product->fresh()->moderation_status);
            $this->assertFalse((bool) $product->fresh()->is_active);
            $this->assertDatabaseHas('product_moderation_runs', [
                'product_id' => $product->id,
                'provider' => 'fake',
                'status' => 'failed',
            ]);
        }
    }

    public function test_shadow_mode_records_allow_without_approving_product(): void
    {
        config()->set('moderation.mode', 'shadow');
        $product = Product::factory()->pendingModeration()->create([
            'is_active' => true,
            'moderation_version' => 2,
        ]);
        $client = $this->clientReturning(new ProductModerationResult('allow', 0.03));

        (new ModerateProductJob($product->id, 2))->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );

        $product->refresh();
        $this->assertSame(ProductModerationStatus::Pending, $product->moderation_status);
        $this->assertNull($product->moderated_version);
        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'decision' => 'allow',
            'status' => 'completed',
        ]);
    }

    public function test_shadow_provider_failure_does_not_change_product_status(): void
    {
        config()->set('moderation.mode', 'shadow');
        config()->set('queue.default', 'database');
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $client = new class implements AiModerationClient
        {
            public function moderate(ProductModerationPayload $payload): ProductModerationResult
            {
                throw new RuntimeException('Provider unavailable');
            }
        };

        try {
            (new ModerateProductJob($product->id, $product->moderation_version))->handle(
                $client,
                app(ProductModerationPayloadFactory::class),
                app(ProductAutoApprovalPolicy::class)
            );
        } catch (RuntimeException) {
            $this->assertSame(ProductModerationStatus::Pending, $product->fresh()->moderation_status);
            $this->assertTrue((bool) $product->fresh()->is_active);
        }
    }

    public function test_completed_shadow_run_is_not_sent_to_provider_twice(): void
    {
        config()->set('moderation.mode', 'shadow');
        $product = Product::factory()->pendingModeration()->create();
        $client = new class implements AiModerationClient
        {
            public int $calls = 0;

            public function moderate(ProductModerationPayload $payload): ProductModerationResult
            {
                $this->calls++;

                return new ProductModerationResult('allow', 0.01);
            }
        };
        $job = new ModerateProductJob($product->id, $product->moderation_version);

        $job->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );
        $job->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );

        $this->assertSame(1, $client->calls);
        $this->assertDatabaseCount('product_moderation_runs', 1);
    }

    public function test_sync_provider_failure_is_recorded_without_escaping(): void
    {
        config()->set('queue.default', 'sync');
        config()->set('moderation.mode', 'auto');
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $client = new class implements AiModerationClient
        {
            public function moderate(ProductModerationPayload $payload): ProductModerationResult
            {
                throw new RuntimeException('Provider unavailable');
            }
        };

        (new ModerateProductJob($product->id, $product->moderation_version))->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );

        $this->assertSame(ProductModerationStatus::NeedsReview, $product->fresh()->moderation_status);
        $this->assertFalse((bool) $product->fresh()->is_active);
        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'status' => 'failed',
        ]);
    }

    public function test_permanent_provider_error_is_not_retried(): void
    {
        config()->set('queue.default', 'database');
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $client = new class implements AiModerationClient
        {
            public function moderate(ProductModerationPayload $payload): ProductModerationResult
            {
                throw new AiModerationProviderException('AI API returned HTTP 401.');
            }
        };

        (new ModerateProductJob($product->id, $product->moderation_version))->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );

        $product->refresh();

        $this->assertSame(ProductModerationStatus::NeedsReview, $product->moderation_status);
        $this->assertStringContainsString('временно недоступна', $product->moderation_summary);
        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'status' => 'failed',
        ]);
    }

    public function test_retryable_provider_error_is_rethrown_for_queue_retry(): void
    {
        config()->set('queue.default', 'database');
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $client = new class implements AiModerationClient
        {
            public function moderate(ProductModerationPayload $payload): ProductModerationResult
            {
                throw new AiModerationProviderException(
                    'AI API returned HTTP 429.',
                    retryable: true
                );
            }
        };

        $this->expectException(AiModerationProviderException::class);

        (new ModerateProductJob($product->id, $product->moderation_version))->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );
    }

    public function test_exhausted_queue_attempts_send_product_to_manual_review(): void
    {
        config()->set('moderation.mode', 'auto');
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);

        (new ModerateProductJob($product->id, $product->moderation_version))
            ->failed(new RuntimeException('Provider unavailable'));

        $product->refresh();

        $this->assertSame(ProductModerationStatus::NeedsReview, $product->moderation_status);
        $this->assertStringContainsString('временно недоступна', $product->moderation_summary);
        $this->assertFalse((bool) $product->is_active);
    }

    public function test_allow_with_high_risk_goes_to_manual_review(): void
    {
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $this->allowAutoApprovalFor($product);
        $client = $this->clientReturning(new ProductModerationResult('allow', 0.4));

        $this->handle($product, $client);

        $this->assertSame(ProductModerationStatus::NeedsReview, $product->fresh()->moderation_status);
        $this->assertStringContainsString('риска', $product->fresh()->moderation_summary);
    }

    public function test_allow_in_excluded_category_goes_to_manual_review(): void
    {
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $this->createLocalAllowRun($product);
        config()->set('moderation.auto_approve.excluded_category_aliases', [$product->category->alias]);

        $this->handle($product, $this->clientReturning(new ProductModerationResult('allow', 0.01)));

        $this->assertSame(ProductModerationStatus::NeedsReview, $product->fresh()->moderation_status);
    }

    public function test_empty_excluded_category_list_allows_auto_approval(): void
    {
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $this->createLocalAllowRun($product);
        config()->set('moderation.auto_approve.excluded_category_aliases', []);

        $this->handle($product, $this->clientReturning(new ProductModerationResult('allow', 0.01)));

        $this->assertSame(ProductModerationStatus::Approved, $product->fresh()->moderation_status);
    }

    public function test_allow_with_violations_goes_to_manual_review(): void
    {
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $this->allowAutoApprovalFor($product);

        $this->handle($product, $this->clientReturning(new ProductModerationResult(
            'allow',
            0.01,
            [['code' => 'spam']]
        )));

        $this->assertSame(ProductModerationStatus::NeedsReview, $product->fresh()->moderation_status);
    }

    public function test_violation_evidence_is_shown_to_seller_when_ai_message_is_empty(): void
    {
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $this->allowAutoApprovalFor($product);

        $this->handle($product, $this->clientReturning(new ProductModerationResult(
            'review',
            0.8,
            [[
                'code' => 'profanity',
                'confidence' => 0.98,
                'evidence' => 'В названии обнаружена ненормативная лексика.',
            ]]
        )));

        $product->refresh();

        $this->assertSame(ProductModerationStatus::ChangesRequired, $product->moderation_status);
        $this->assertStringContainsString('ненормативная лексика', $product->moderation_summary);
        $this->assertStringContainsString('Исправьте карточку товара', $product->moderation_summary);
        $this->assertStringContainsString('отправьте её на модерацию повторно', $product->moderation_summary);
    }

    public function test_out_of_scope_ai_requirements_do_not_block_auto_approval(): void
    {
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        $this->allowAutoApprovalFor($product);

        $this->handle($product, $this->clientReturning(new ProductModerationResult(
            decision: 'review',
            riskScore: 0.85,
            violations: [[
                'code' => 'certificate_required',
                'confidence' => 0.9,
                'evidence' => 'Для товара с аккумулятором нужно указать наличие сертификата.',
                'suggestion' => 'Добавьте сертификат и технические характеристики.',
            ]],
            sellerMessage: 'Укажите наличие сертификата и подробные характеристики товара.',
        )));

        $product->refresh();

        $this->assertSame(ProductModerationStatus::Approved, $product->moderation_status);
        $this->assertTrue((bool) $product->is_active);
        $this->assertNull($product->moderation_summary);
        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'decision' => 'allow',
            'status' => 'completed',
        ]);
    }

    private function handle(Product $product, AiModerationClient $client): void
    {
        (new ModerateProductJob($product->id, $product->moderation_version))->handle(
            $client,
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class)
        );
    }

    private function allowAutoApprovalFor(Product $product): void
    {
        config()->set('moderation.auto_approve.excluded_category_aliases', []);
        $this->createLocalAllowRun($product);
    }

    private function createLocalAllowRun(Product $product): void
    {
        ProductModerationRun::query()->create([
            'product_id' => $product->id,
            'content_version' => $product->moderation_version,
            'trigger' => 'seller_submit',
            'provider' => 'local',
            'status' => 'completed',
            'decision' => 'allow',
            'risk_score' => 0,
            'request_fingerprint' => hash('sha256', 'local-'.$product->id),
        ]);
    }

    private function clientReturning(ProductModerationResult $result): AiModerationClient
    {
        return new class($result) implements AiModerationClient
        {
            public function __construct(
                private readonly ProductModerationResult $result
            ) {}

            public function moderate(ProductModerationPayload $payload): ProductModerationResult
            {
                return $this->result;
            }
        };
    }
}
