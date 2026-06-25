<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Exceptions\ProductModerationException;
use App\Models\Image;
use App\Models\Package;
use App\Models\Product;
use App\Models\SellerPackage;
use App\Models\User;
use App\Notifications\ProductModerationDecisionNotification;
use App\Jobs\ModerateProductJob;
use App\Services\ProductModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProductModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductModerationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProductModerationService::class);
    }

    public function test_seller_can_submit_draft_product_and_action_is_recorded(): void
    {
        $product = Product::factory()->draft()->create();
        $this->assignPackage($product);
        Image::factory()->create(['product_id' => $product->id]);
        $actor = $product->seller->user;

        $submitted = $this->service->submit($product, $actor);

        $this->assertSame(ProductModerationStatus::Pending, $submitted->moderation_status);
        $this->assertFalse((bool) $submitted->is_active);
        $this->assertTrue($submitted->publication_requested);
        $this->assertNotNull($submitted->moderation_submitted_at);
        $this->assertDatabaseHas('product_moderation_actions', [
            'product_id' => $product->id,
            'actor_user_id' => $actor->id,
            'action' => 'submitted',
            'previous_status' => ProductModerationStatus::Draft->value,
            'new_status' => ProductModerationStatus::Pending->value,
        ]);
    }

    public function test_submission_requires_publishable_product(): void
    {
        $product = Product::factory()->draft()->create();
        $this->assignPackage($product);

        $this->expectException(ProductModerationException::class);

        $this->service->submit($product, $product->seller->user);
    }

    public function test_submission_is_blocked_by_contact_in_title(): void
    {
        $product = Product::factory()->draft()->create([
            'name' => 'Телефон +373 777 12345',
            'desc' => 'Телефон в хорошем состоянии и полной комплектации.',
        ]);
        $this->assignPackage($product);
        Image::factory()->create(['product_id' => $product->id]);

        $this->expectException(ProductModerationException::class);

        $this->service->submit($product, $product->seller->user);
    }

    public function test_high_risk_local_finding_goes_to_manual_review_and_creates_run(): void
    {
        $product = Product::factory()->draft()->create([
            'name' => 'Коллекционный предмет',
            'desc' => 'Боеприпасы для частной коллекции в оригинальной упаковке.',
        ]);
        $this->assignPackage($product);
        Image::factory()->create(['product_id' => $product->id]);

        $submitted = $this->service->submit($product, $product->seller->user);

        $this->assertSame(ProductModerationStatus::NeedsReview, $submitted->moderation_status);
        $this->assertSame('local_rules', $submitted->moderation_source);
        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'provider' => 'local',
            'decision' => 'review',
            'status' => 'completed',
        ]);
    }

    public function test_submission_dispatches_ai_job_when_feature_is_enabled(): void
    {
        Queue::fake();
        config()->set('moderation.ai_enabled', true);
        $product = Product::factory()->draft()->create([
            'desc' => 'Подробное описание обычного товара без нарушений.',
        ]);
        $this->assignPackage($product);
        Image::factory()->create(['product_id' => $product->id]);

        $this->service->submit($product, $product->seller->user);

        Queue::assertPushed(
            ModerateProductJob::class,
            fn (ModerateProductJob $job): bool =>
                $job->productId === $product->id
                && $job->contentVersion === $product->moderation_version
        );
    }

    public function test_administrator_can_approve_pending_product(): void
    {
        Notification::fake();
        $administrator = User::factory()->create();
        $product = Product::factory()->pendingModeration()->create([
            'moderation_version' => 3,
            'moderated_version' => null,
            'is_active' => true,
        ]);

        $approved = $this->service->approve($product, $administrator, 'Проверено');

        $this->assertSame(ProductModerationStatus::Approved, $approved->moderation_status);
        $this->assertSame(3, $approved->moderated_version);
        $this->assertSame('manual', $approved->moderation_source);
        $this->assertSame($administrator->id, $approved->moderated_by_user_id);
        $this->assertDatabaseHas('product_moderation_actions', [
            'product_id' => $product->id,
            'action' => 'approved',
            'comment' => 'Проверено',
        ]);
        Notification::assertSentTo(
            $product->seller->user,
            ProductModerationDecisionNotification::class
        );
    }

    public function test_approval_publishes_product_when_publication_was_requested(): void
    {
        $administrator = User::factory()->create();
        $product = Product::factory()->pendingModeration()->create([
            'publication_requested' => true,
        ]);

        $approved = $this->service->approve($product, $administrator);

        $this->assertTrue($approved->is_active);
    }

    public function test_approval_keeps_product_hidden_without_publication_request(): void
    {
        $administrator = User::factory()->create();
        $product = Product::factory()->pendingModeration()->create([
            'publication_requested' => false,
        ]);

        $approved = $this->service->approve($product, $administrator);

        $this->assertFalse($approved->is_active);
        $this->assertSame(ProductModerationStatus::Approved, $approved->moderation_status);
    }

    public function test_request_changes_hides_product_and_preserves_reason(): void
    {
        $administrator = User::factory()->create();
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);

        $result = $this->service->requestChanges($product, $administrator, 'Исправьте описание');

        $this->assertSame(ProductModerationStatus::ChangesRequired, $result->moderation_status);
        $this->assertFalse((bool) $result->is_active);
        $this->assertSame('Исправьте описание', $result->moderation_summary);
    }

    public function test_reject_requires_pending_state(): void
    {
        $product = Product::factory()->draft()->create();

        $this->expectException(ProductModerationException::class);

        $this->service->reject($product, User::factory()->create(), 'fraud', 'Причина');
    }

    public function test_manual_decision_is_compared_with_shadow_ai_result(): void
    {
        $administrator = User::factory()->create();
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        \App\Models\ProductModerationRun::query()->create([
            'product_id' => $product->id,
            'content_version' => $product->moderation_version,
            'trigger' => 'queue',
            'provider' => 'fake',
            'status' => 'completed',
            'decision' => 'allow',
            'risk_score' => 0.1,
            'request_fingerprint' => hash('sha256', 'comparison'),
        ]);

        $this->service->approve($product, $administrator);

        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'decision' => 'allow',
            'manual_decision' => 'allow',
            'agrees_with_manual' => true,
        ]);
    }

    public function test_ai_disagreement_with_manual_rejection_is_recorded(): void
    {
        $administrator = User::factory()->create();
        $product = Product::factory()->pendingModeration()->create(['is_active' => true]);
        \App\Models\ProductModerationRun::query()->create([
            'product_id' => $product->id,
            'content_version' => $product->moderation_version,
            'trigger' => 'queue',
            'provider' => 'fake',
            'status' => 'completed',
            'decision' => 'allow',
            'risk_score' => 0.1,
            'request_fingerprint' => hash('sha256', 'disagreement'),
        ]);

        $this->service->reject($product, $administrator, 'other', 'Отклонено вручную');

        $this->assertDatabaseHas('product_moderation_runs', [
            'product_id' => $product->id,
            'manual_decision' => 'block',
            'agrees_with_manual' => false,
        ]);
    }

    private function assignPackage(Product $product): void
    {
        $package = Package::factory()->withLimits(10, 5)->create();

        SellerPackage::factory()->create([
            'seller_id' => $product->seller_id,
            'package_id' => $package->id,
            'is_active' => true,
            'end_date' => now()->addMonth(),
        ]);
    }
}
