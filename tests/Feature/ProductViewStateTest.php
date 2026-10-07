<?php

namespace Tests\Feature;

use App\Enums\ProductDisplayState;
use App\Enums\ProductModerationStatus;
use App\Enums\ProductPrimaryAction;
use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Services\ProductStateTransitionService;
use App\Services\ProductViewStateFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductViewStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_action_matrix_matches_moderation_statuses(): void
    {
        config()->set('moderation.ai_enabled', true);

        $cases = [
            ProductModerationStatus::Draft->value => ['submit' => true],
            ProductModerationStatus::Pending->value => [],
            ProductModerationStatus::Processing->value => [],
            ProductModerationStatus::NeedsReview->value => [],
            ProductModerationStatus::ChangesRequired->value => ['submit' => true],
            ProductModerationStatus::Rejected->value => [],
        ];

        foreach ($cases as $status => $expected) {
            $product = $this->product([
                'moderation_status' => $status,
                'is_active' => false,
                'moderated_version' => null,
            ]);
            $state = app(ProductViewStateFactory::class)->make($product);

            $this->assertSame($expected['submit'] ?? false, $state->canSubmit, $status);
            $this->assertFalse($state->canPublish, $status);
            $this->assertFalse($state->canPushUp, $status);
        }
    }

    public function test_technical_failure_is_presented_to_seller_as_manual_review(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Failed,
            'moderation_summary' => 'Gemini API connection failed.',
            'is_active' => false,
        ]);

        $state = app(ProductViewStateFactory::class)->make($product);

        $this->assertSame('На ручной проверке', $state->moderationLabel);
        $this->assertSame(
            'Товар передан администратору на дополнительную проверку.',
            $state->description
        );
    }

    public function test_approved_inactive_product_can_only_be_published(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 3,
            'moderated_version' => 3,
            'is_active' => false,
        ]);
        $state = app(ProductViewStateFactory::class)->make($product);

        $this->assertTrue($state->canPublish);
        $this->assertFalse($state->canUnpublish);
        $this->assertFalse($state->canPushUp);
        $this->assertSame('Одобрен', $state->moderationLabel);
        $this->assertSame('Не опубликован', $state->publicationLabel);
    }

    public function test_approved_active_product_is_public_and_can_be_managed(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Approved,
            'moderation_version' => 3,
            'moderated_version' => 3,
            'is_active' => true,
        ]);
        $state = app(ProductViewStateFactory::class)->make($product);

        $this->assertTrue($state->isPublic);
        $this->assertTrue($state->canUnpublish);
        $this->assertTrue($state->canPushUp);
        $this->assertTrue($state->canOpenPublicPage);
        $this->assertSame('Опубликован', $state->publicationLabel);
        $this->assertSame(ProductDisplayState::Published, $state->displayState);
        $this->assertSame(ProductPrimaryAction::PushUp, $state->primaryAction);
    }

    public function test_display_state_exposes_one_primary_action_for_seller(): void
    {
        $draft = app(ProductViewStateFactory::class)->make($this->product([
            'moderation_status' => ProductModerationStatus::Draft,
            'is_active' => false,
        ]));
        $review = app(ProductViewStateFactory::class)->make($this->product([
            'moderation_status' => ProductModerationStatus::NeedsReview,
            'is_active' => false,
        ]));

        $this->assertSame(ProductDisplayState::Draft, $draft->displayState);
        $this->assertSame(ProductPrimaryAction::Submit, $draft->primaryAction);
        $this->assertSame(ProductDisplayState::ManualReview, $review->displayState);
        $this->assertSame(ProductPrimaryAction::None, $review->primaryAction);
    }

    public function test_manual_decision_is_not_allowed_while_ai_is_processing(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Processing,
        ]);

        $this->assertFalse(
            app(ProductStateTransitionService::class)->canModerateManually($product)
        );
    }

    public function test_manual_decision_is_allowed_after_technical_failure(): void
    {
        $product = $this->product([
            'moderation_status' => ProductModerationStatus::Failed,
        ]);

        $this->assertTrue(
            app(ProductStateTransitionService::class)->canModerateManually($product)
        );
    }

    public function test_changes_required_has_clear_seller_status_and_edit_action(): void
    {
        $product = Product::factory()->create([
            'moderation_status' => ProductModerationStatus::ChangesRequired,
            'moderation_summary' => 'Удалите запрещённое слово.',
            'is_active' => false,
        ]);

        $state = $product->view_state;

        $this->assertSame('Нужно редактировать', $state->moderationLabel);
        $this->assertSame(ProductPrimaryAction::Edit, $state->primaryAction);
        $this->assertSame('Удалите запрещённое слово.', $state->description);
    }

    private function product(array $attributes): Product
    {
        $seller = Seller::factory()->create();

        return Product::factory()->create(array_merge([
            'seller_id' => $seller->id,
            'category_id' => Category::factory()->create()->id,
        ], $attributes));
    }
}
