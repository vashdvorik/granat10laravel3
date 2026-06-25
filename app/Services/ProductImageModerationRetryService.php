<?php

namespace App\Services;

use App\Enums\ProductModerationStatus;
use App\Jobs\ModerateProductImagesJob;
use App\Models\Product;
use App\Models\ProductModerationRun;

class ProductImageModerationRetryService
{
    public function retry(Product $product): bool
    {
        $product->refresh();

        if (! $this->canRetry($product)) {
            return false;
        }

        $product->forceFill([
            'is_active' => false,
            'moderation_status' => ProductModerationStatus::Processing,
            'moderation_summary' => null,
            'moderation_source' => 'ai_image',
        ])->save();

        ProductModerationRun::query()
            ->where('product_id', $product->id)
            ->where('content_version', $product->moderation_version)
            ->where('provider', 'like', 'image_%')
            ->whereIn('status', ['failed', 'completed'])
            ->update([
                'status' => 'retrying',
                'error_code' => null,
                'error_message' => null,
                'finished_at' => null,
            ]);

        ModerateProductImagesJob::dispatch($product->id, $product->moderation_version)
            ->afterCommit();

        return true;
    }

    public function canRetry(Product $product): bool
    {
        if (
            ! config('moderation.images.enabled')
            || ! $product->images()->exists()
            || ! in_array($product->moderation_status, [
                ProductModerationStatus::NeedsReview,
                ProductModerationStatus::Failed,
            ], true)
        ) {
            return false;
        }

        $latestImageRun = ProductModerationRun::query()
            ->where('product_id', $product->id)
            ->where('content_version', $product->moderation_version)
            ->where('provider', 'like', 'image_%')
            ->latest('id')
            ->first();

        return $latestImageRun !== null
            && in_array($latestImageRun->status, ['failed', 'completed'], true)
            && $latestImageRun->decision !== 'allow';
    }
}
