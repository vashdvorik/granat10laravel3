<?php

namespace Tests\Feature;

use App\Contracts\ProductImageModerationClient;
use App\Data\ProductModerationPayload;
use App\Data\ProductModerationResult;
use App\Enums\ProductModerationStatus;
use App\Jobs\ModerateProductImagesJob;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Services\AiModerationOutcomeStatusResolver;
use App\Services\ProductAutoApprovalPolicy;
use App\Services\ProductModerationFeedbackService;
use App\Services\ProductModerationPayloadFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerateProductImagesJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_allow_approves_product_after_text_allow(): void
    {
        config()->set('moderation.mode', 'auto');
        config()->set('moderation.images.enabled', true);
        $product = Product::factory()->pendingModeration()->create([
            'moderation_status' => ProductModerationStatus::Processing,
            'publication_requested' => true,
            'is_active' => false,
            'moderation_version' => 3,
        ]);
        Image::factory()->for($product)->create();
        $this->createLocalAllowRun($product);
        $this->createTextAllowRun($product);

        $this->handle($product, new ProductModerationResult(
            decision: 'allow',
            riskScore: 0.01,
            imagesAnalyzed: true,
        ));

        $product->refresh();

        $this->assertSame(ProductModerationStatus::Approved, $product->moderation_status);
        $this->assertTrue((bool) $product->is_active);
        $this->assertSame(3, $product->moderated_version);
        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'provider' => 'image_gemini',
            'status' => 'completed',
            'decision' => 'allow',
        ]);
    }

    public function test_image_violation_requires_seller_changes(): void
    {
        config()->set('moderation.mode', 'auto');
        config()->set('moderation.images.enabled', true);
        $product = Product::factory()->pendingModeration()->create([
            'moderation_status' => ProductModerationStatus::Processing,
            'publication_requested' => true,
            'is_active' => false,
        ]);
        Image::factory()->for($product)->create();
        $this->createLocalAllowRun($product);
        $this->createTextAllowRun($product);

        $this->handle($product, new ProductModerationResult(
            decision: 'review',
            riskScore: 0.8,
            violations: [[
                'code' => 'prohibited_image',
                'confidence' => 0.95,
                'evidence' => 'На фото обнаружен запрещённый контент.',
                'field' => 'images',
                'suggestion' => 'Замените фотографию товара.',
            ]],
            sellerMessage: 'Замените фотографию товара.',
            imagesAnalyzed: true,
        ));

        $product->refresh();

        $this->assertSame(ProductModerationStatus::ChangesRequired, $product->moderation_status);
        $this->assertFalse((bool) $product->is_active);
        $this->assertStringContainsString('Замените фотографию товара', $product->moderation_summary);
    }

    private function handle(Product $product, ProductModerationResult $result): void
    {
        (new ModerateProductImagesJob($product->id, $product->moderation_version))->handle(
            new class($result) implements ProductImageModerationClient
            {
                public function __construct(private readonly ProductModerationResult $result) {}

                public function moderate(ProductModerationPayload $payload): ProductModerationResult
                {
                    return $this->result;
                }
            },
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class),
            app(ProductModerationFeedbackService::class),
            app(AiModerationOutcomeStatusResolver::class),
        );
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

    private function createTextAllowRun(Product $product): void
    {
        ProductModerationRun::query()->create([
            'product_id' => $product->id,
            'content_version' => $product->moderation_version,
            'trigger' => 'queue',
            'provider' => 'openai_compatible',
            'status' => 'completed',
            'decision' => 'allow',
            'risk_score' => 0,
            'request_fingerprint' => hash('sha256', 'text-'.$product->id),
        ]);
    }
}
