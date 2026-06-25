<?php

namespace App\Services;

use App\Enums\ProductModerationStatus;
use App\Events\ProductModerationDecided;
use App\Exceptions\ProductModerationException;
use App\Jobs\ModerateProductJob;
use App\Models\Product;
use App\Models\ProductModerationAction;
use App\Models\ProductModerationRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProductModerationService
{
    public function __construct(
        private readonly ProductLimitService $productLimitService,
        private readonly ProductLocalModerationAnalyzer $localAnalyzer
    ) {}

    public function submit(Product $product, User $actor): Product
    {
        return DB::transaction(function () use ($product, $actor): Product {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            if (! in_array($product->moderation_status, [
                ProductModerationStatus::Draft,
                ProductModerationStatus::ChangesRequired,
            ], true)) {
                throw new ProductModerationException('Товар уже отправлен или прошёл модерацию.');
            }

            $error = $this->productLimitService->activationError($product);

            if ($error !== null) {
                throw new ProductModerationException($error);
            }

            $analysis = $this->localAnalyzer->analyze($product);
            $blockingFindings = collect($analysis['findings'])
                ->where('blocking', true)
                ->pluck('message')
                ->all();

            if ($blockingFindings !== []) {
                throw new ProductModerationException(implode(' ', $blockingFindings));
            }

            $previousStatus = $product->moderation_status;
            $newStatus = $analysis['decision'] === 'review'
                ? ProductModerationStatus::NeedsReview
                : ProductModerationStatus::Pending;

            $product->forceFill([
                'is_active' => false,
                'publication_requested' => true,
                'moderation_status' => $newStatus,
                'moderation_source' => 'local_rules',
                'moderation_summary' => $this->analysisSummary($analysis['findings']),
                'moderation_risk_score' => $analysis['risk_score'],
                'moderation_submitted_at' => now(),
                'moderated_at' => null,
                'moderated_by_user_id' => null,
            ])->save();

            ProductModerationRun::query()->updateOrCreate([
                'product_id' => $product->id,
                'content_version' => $product->moderation_version,
                'request_fingerprint' => $analysis['fingerprint'],
                'provider' => 'local',
            ], [
                'trigger' => 'seller_submit',
                'model' => 'rules-v1',
                'prompt_version' => 'local-rules-v1',
                'status' => 'completed',
                'decision' => $analysis['decision'],
                'risk_score' => $analysis['risk_score'],
                'categories' => collect($analysis['findings'])->pluck('code')->values()->all(),
                'reasons' => $analysis['findings'],
                'text_result' => $analysis,
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            $this->recordAction($product, $actor, 'submitted', $previousStatus, $newStatus);

            if (config('moderation.ai_enabled')) {
                ModerateProductJob::dispatch($product->id, $product->moderation_version)
                    ->afterCommit();
            }

            return $product->refresh();
        });
    }

    /**
     * @param  array<int, array{message: string}>  $findings
     */
    private function analysisSummary(array $findings): ?string
    {
        if ($findings === []) {
            return null;
        }

        return collect($findings)->pluck('message')->unique()->implode(' ');
    }

    public function approve(Product $product, User $actor, ?string $comment = null): Product
    {
        return $this->decide(
            $product,
            $actor,
            ProductModerationStatus::Approved,
            'approved',
            $comment,
            null,
            true
        );
    }

    public function requestChanges(Product $product, User $actor, string $comment): Product
    {
        return $this->decide(
            $product,
            $actor,
            ProductModerationStatus::ChangesRequired,
            'changes_requested',
            $comment,
            null,
            false
        );
    }

    public function reject(
        Product $product,
        User $actor,
        string $reasonCode,
        string $comment
    ): Product {
        return $this->decide(
            $product,
            $actor,
            ProductModerationStatus::Rejected,
            'rejected',
            $comment,
            $reasonCode,
            false
        );
    }

    public function invalidate(Product $product): Product
    {
        $product->forceFill([
            'is_active' => false,
            'moderation_status' => ProductModerationStatus::Draft,
            'moderation_version' => $product->moderation_version + 1,
            'moderated_version' => null,
            'moderation_source' => null,
            'moderation_summary' => null,
            'moderation_risk_score' => null,
            'moderation_submitted_at' => null,
            'moderated_at' => null,
            'moderated_by_user_id' => null,
        ])->save();

        return $product->refresh();
    }

    private function decide(
        Product $product,
        User $actor,
        ProductModerationStatus $newStatus,
        string $action,
        ?string $comment,
        ?string $reasonCode,
        bool $keepPublicationIntent
    ): Product {
        return DB::transaction(function () use (
            $product,
            $actor,
            $newStatus,
            $action,
            $comment,
            $reasonCode,
            $keepPublicationIntent
        ): Product {
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);

            if (! in_array($product->moderation_status, [
                ProductModerationStatus::Pending,
                ProductModerationStatus::Processing,
                ProductModerationStatus::NeedsReview,
                ProductModerationStatus::Failed,
            ], true)) {
                throw new ProductModerationException('Решение можно принять только по товару на проверке.');
            }

            $previousStatus = $product->moderation_status;
            $product->forceFill([
                'is_active' => $newStatus === ProductModerationStatus::Approved
                    ? ($keepPublicationIntent && $product->publication_requested)
                    : false,
                'moderation_status' => $newStatus,
                'moderated_version' => $newStatus === ProductModerationStatus::Approved
                    ? $product->moderation_version
                    : null,
                'moderation_source' => 'manual',
                'moderation_summary' => $comment,
                'moderated_at' => now(),
                'moderated_by_user_id' => $actor->id,
            ])->save();

            $this->recordAction(
                $product,
                $actor,
                $action,
                $previousStatus,
                $newStatus,
                $reasonCode,
                $comment
            );

            $this->compareWithAiDecision($product, $newStatus);

            ProductModerationDecided::dispatch($product->id);

            return $product->refresh();
        });
    }

    private function compareWithAiDecision(
        Product $product,
        ProductModerationStatus $manualStatus
    ): void {
        $manualDecision = match ($manualStatus) {
            ProductModerationStatus::Approved => 'allow',
            ProductModerationStatus::Rejected => 'block',
            default => 'review',
        };

        $run = ProductModerationRun::query()
            ->where('product_id', $product->id)
            ->where('content_version', $product->moderation_version)
            ->where('provider', '!=', 'local')
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        if ($run === null || $run->decision === null) {
            return;
        }

        $run->update([
            'manual_decision' => $manualDecision,
            'agrees_with_manual' => $run->decision === $manualDecision,
            'compared_at' => now(),
        ]);
    }

    private function recordAction(
        Product $product,
        User $actor,
        string $action,
        ProductModerationStatus $previousStatus,
        ProductModerationStatus $newStatus,
        ?string $reasonCode = null,
        ?string $comment = null
    ): void {
        ProductModerationAction::query()->create([
            'product_id' => $product->id,
            'actor_user_id' => $actor->id,
            'action' => $action,
            'reason_code' => $reasonCode,
            'comment' => $comment,
            'previous_status' => $previousStatus->value,
            'new_status' => $newStatus->value,
        ]);
    }
}
