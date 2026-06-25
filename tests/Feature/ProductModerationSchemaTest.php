<?php

namespace Tests\Feature;

use App\Enums\ProductModerationStatus;
use App\Models\Product;
use App\Models\ProductModerationAction;
use App\Models\ProductModerationRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductModerationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderation_schema_and_relations_are_available(): void
    {
        $this->assertTrue(Schema::hasColumns('products', [
            'moderation_status',
            'publication_requested',
            'moderation_version',
            'moderated_version',
            'moderation_source',
            'moderation_risk_score',
            'moderation_summary',
            'moderation_submitted_at',
            'moderated_at',
            'moderated_by_user_id',
        ]));
        $this->assertTrue(Schema::hasTable('product_moderation_runs'));
        $this->assertTrue(Schema::hasTable('product_moderation_actions'));

        $product = Product::factory()->create();
        $run = ProductModerationRun::create([
            'product_id' => $product->id,
            'content_version' => 1,
            'trigger' => 'test',
            'status' => 'completed',
            'decision' => 'allow',
            'request_fingerprint' => hash('sha256', 'test'),
        ]);
        $actor = User::factory()->create();
        $action = ProductModerationAction::create([
            'product_id' => $product->id,
            'product_moderation_run_id' => $run->id,
            'actor_user_id' => $actor->id,
            'action' => 'approve',
            'previous_status' => ProductModerationStatus::NeedsReview->value,
            'new_status' => ProductModerationStatus::Approved->value,
        ]);

        $this->assertTrue($product->moderationRuns->contains($run));
        $this->assertTrue($product->moderationActions->contains($action));
        $this->assertTrue($action->actor->is($actor));
    }

    public function test_new_database_product_defaults_to_draft(): void
    {
        $product = Product::query()->create([
            'name' => 'Новый товар',
            'price' => 100,
            'alias' => 'new-draft-product',
            'is_active' => false,
            'category_id' => \App\Models\Category::factory()->create()->id,
            'seller_id' => \App\Models\Seller::factory()->create()->id,
        ]);

        $product->refresh();

        $this->assertSame(ProductModerationStatus::Draft, $product->moderation_status);
        $this->assertSame(1, $product->moderation_version);
        $this->assertNull($product->moderated_version);
        $this->assertFalse($product->publication_requested);
    }

    public function test_legacy_backfill_shape_preserves_active_and_inactive_intent(): void
    {
        $active = Product::factory()->create([
            'is_active' => true,
            'publication_requested' => true,
        ]);
        $inactive = Product::factory()->create([
            'is_active' => false,
            'publication_requested' => false,
        ]);

        DB::table('products')
            ->whereIn('id', [$active->id, $inactive->id])
            ->update([
                'moderation_status' => ProductModerationStatus::Approved->value,
                'moderation_version' => 1,
                'moderated_version' => 1,
                'moderation_source' => 'legacy_migration',
            ]);

        $this->assertTrue((bool) $active->fresh()->is_active);
        $this->assertFalse((bool) $inactive->fresh()->is_active);
        $this->assertTrue($active->fresh()->publication_requested);
        $this->assertFalse($inactive->fresh()->publication_requested);
        $this->assertSame(ProductModerationStatus::Approved, $active->fresh()->moderation_status);
        $this->assertSame(ProductModerationStatus::Approved, $inactive->fresh()->moderation_status);
    }

    public function test_failed_products_are_represented_as_manual_review(): void
    {
        $product = Product::factory()->create([
            'moderation_status' => ProductModerationStatus::NeedsReview,
            'moderation_summary' => 'Товар передан администратору для дополнительной проверки.',
            'is_active' => false,
        ]);

        $this->assertSame(ProductModerationStatus::NeedsReview, $product->moderation_status);
        $this->assertFalse($product->is_active);
    }
}
