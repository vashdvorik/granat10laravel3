<?php

namespace App\Services;

use App\Jobs\ModerateProductJob;
use App\Enums\ProductModerationStatus;
use App\Models\Product;
use App\Models\ProductModerationRun;

class ProductModerationRetryService
{
    public function __construct(
        private readonly ProductLimitService $productLimitService
    ) {
    }

    public function retry(Product $product): bool
    {
        $product->refresh();

        if (
            ! $this->canRetry($product)
            || $this->productLimitService->activationError($product) !== null
        ) {
            return false;
        }

        $product->forceFill([
            'is_active' => false,
            'moderation_status' => \App\Enums\ProductModerationStatus::Pending,
            'moderation_summary' => null,
            'moderation_source' => 'ai',
        ])->save();

        ProductModerationRun::query()
            ->where('product_id', $product->id)
            ->where('content_version', $product->moderation_version)
            ->where('status', 'failed')
            ->update([
                'status' => 'retrying',
                'error_code' => null,
                'error_message' => null,
                'finished_at' => null,
            ]);

        ModerateProductJob::dispatch($product->id, $product->moderation_version)
            ->afterCommit();

        return true;
    }

    public function canRetry(Product $product): bool
    {
        if (
            ! config('moderation.ai_enabled')
            || $product->moderation_status !== ProductModerationStatus::NeedsReview
        ) {
            return false;
        }

        $latestRun = ProductModerationRun::query()
            ->where('product_id', $product->id)
            ->where('content_version', $product->moderation_version)
            ->where('provider', '!=', 'local')
            ->latest('id')
            ->first();

        return $latestRun?->status === 'failed';
    }
}
