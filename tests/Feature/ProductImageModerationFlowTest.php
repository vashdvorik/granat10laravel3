<?php

namespace Tests\Feature;

use App\Contracts\AiModerationClient;
use App\Contracts\ProductImageModerationClient;
use App\Data\ProductModerationPayload;
use App\Data\ProductModerationResult;
use App\Enums\ProductModerationStatus;
use App\Jobs\ModerateProductImagesJob;
use App\Jobs\ModerateProductJob;
use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Models\Seller;
use App\Models\User;
use App\Services\AiModerationOutcomeStatusResolver;
use App\Services\ProductAutoApprovalPolicy;
use App\Services\ProductModerationFeedbackService;
use App\Services\ProductModerationIssuePresenter;
use App\Services\ProductModerationPayloadFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductImageModerationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_text_allow_then_image_review_shows_photo_issue_to_seller(): void
    {
        Queue::fake();
        config()->set('moderation.mode', 'auto');
        config()->set('moderation.images.enabled', true);

        $sellerUser = User::factory()->create();
        $seller = Seller::factory()->create(['user_id' => $sellerUser->id]);
        $category = Category::factory()->create();
        $product = Product::factory()->pendingModeration()->create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'moderation_version' => 7,
            'is_active' => false,
            'publication_requested' => true,
        ]);
        Image::factory()->create([
            'product_id' => $product->id,
            'path' => 'products/problem-flow.jpg',
            'is_main' => true,
            'order' => 1,
        ]);
        $this->createLocalAllowRun($product);

        $this->handleTextModeration($product, new ProductModerationResult(
            decision: 'allow',
            riskScore: 0.01,
            providerRequestId: 'text-request'
        ));

        $product->refresh();
        $this->assertSame(ProductModerationStatus::Processing, $product->moderation_status);
        $this->assertFalse((bool) $product->is_active);
        Queue::assertPushed(ModerateProductImagesJob::class);

        $this->handleImageModeration($product, new ProductModerationResult(
            decision: 'review',
            riskScore: 0.7,
            violations: [[
                'code' => 'wrong_image',
                'confidence' => 0.9,
                'evidence' => 'Фото не относится к товару.',
                'field' => 'images',
                'image_index' => 1,
                'suggestion' => 'Замените фото товара.',
            ]],
            sellerMessage: 'Замените фото товара.',
            imagesAnalyzed: true,
        ));

        $product->refresh();
        $this->assertSame(ProductModerationStatus::ChangesRequired, $product->moderation_status);
        $this->assertFalse((bool) $product->is_active);
        $this->assertNull($product->moderated_version);

        $issues = app(ProductModerationIssuePresenter::class)->forProduct($product);
        $this->assertSame('images', $issues[0]['field']);
        $this->assertSame(1, $issues[0]['image_index']);
        $this->assertStringContainsString('problem-flow.jpg', $issues[0]['image_url']);

        $this->actingAs($sellerUser)
            ->get(route('account.store.products.show', $product))
            ->assertOk()
            ->assertSee('Фото №1')
            ->assertSee('Фото не относится к товару.')
            ->assertSee('Замените фото товара.');

        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'content_version' => 7,
            'provider' => 'openai_compatible',
            'decision' => 'allow',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'content_version' => 7,
            'provider' => 'image_google',
            'decision' => 'review',
            'status' => 'completed',
        ]);
    }

    private function handleTextModeration(Product $product, ProductModerationResult $result): void
    {
        config()->set('moderation.provider', 'openai_compatible');
        config()->set('moderation.openai_compatible.model', 'test-text-model');

        (new ModerateProductJob($product->id, $product->moderation_version))->handle(
            new class($result) implements AiModerationClient
            {
                public function __construct(private readonly ProductModerationResult $result) {}

                public function moderate(ProductModerationPayload $payload): ProductModerationResult
                {
                    return $this->result;
                }
            },
            app(ProductModerationPayloadFactory::class),
            app(ProductAutoApprovalPolicy::class),
        );
    }

    private function handleImageModeration(Product $product, ProductModerationResult $result): void
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
        ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => $product->moderation_version,
            'trigger' => 'seller_submit',
            'provider' => 'local',
            'status' => 'completed',
            'decision' => 'allow',
            'risk_score' => 0,
            'request_fingerprint' => hash('sha256', 'local-flow-'.$product->id),
        ]);
    }
}
