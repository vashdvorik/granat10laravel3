<?php

namespace App\Services;

use App\Data\ModerationProvider;
use App\Enums\ProductModerationStatus;
use App\Jobs\ModerateProductJob;
use App\Models\Product;
use App\Models\ProductModerationRun;

class ProductModerationRetryService
{
    public function __construct(
        private readonly ProductLimitService $productLimitService
    ) {}

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

        $provider = ModerationProvider::TEXT;
        $model = (string) config('moderation.openrouter.text_model');
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
                'configuration_version' => hash('sha256', "text|{$provider}|{$model}"),
                'trigger' => 'manual_retry',
                'provider' => $provider,
                'model' => $model,
                'prompt_version' => $latestRun->prompt_version,
                'status' => 'retrying',
                'request_fingerprint' => $latestRun->request_fingerprint,
            ]);
        }
        ModerateProductJob::dispatch($product->id, $product->moderation_version, $model, (string) config('moderation.mode'), null, true)
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
