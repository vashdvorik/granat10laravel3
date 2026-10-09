<?php

namespace App\Services;

use App\Data\ModerationProvider;
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

        $provider = ModerationProvider::IMAGE;
        $model = (string) config('moderation.openrouter.image_model');
        $latestRun = ProductModerationRun::query()
            ->where('product_id', $product->id)
            ->where('content_version', $product->moderation_version)
            ->where('provider', $provider)
            ->latest('attempt_number')
            ->first();
        if ($latestRun !== null) {
            ProductModerationRun::query()->create([
                'product_id' => $product->id,
                'content_version' => $product->moderation_version,
                'retry_of_id' => $latestRun->id,
                'attempt_number' => ((int) $latestRun->attempt_number) + 1,
                'configuration_version' => hash('sha256', "image|{$provider}|{$model}"),
                'trigger' => 'manual_retry',
                'provider' => $provider,
                'model' => $model,
                'prompt_version' => $latestRun->prompt_version,
                'status' => 'retrying',
                'request_fingerprint' => $latestRun->request_fingerprint,
            ]);
        }
        ModerateProductImagesJob::dispatch($product->id, $product->moderation_version, $model, (string) config('moderation.mode'), null, true)
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
