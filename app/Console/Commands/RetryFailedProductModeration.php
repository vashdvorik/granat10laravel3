<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Services\ProductModerationRetryService;
use Illuminate\Console\Command;

class RetryFailedProductModeration extends Command
{
    protected $signature = 'moderation:retry-failed
                            {--limit=100 : Maximum products to dispatch}
                            {--force : Run even when AI moderation is disabled}';

    protected $description = 'Dispatch current failed product moderation checks again';

    public function handle(ProductModerationRetryService $retryService): int
    {
        if (! config('moderation.ai_enabled') && ! $this->option('force')) {
            $this->error('AI moderation is disabled.');

            return self::FAILURE;
        }

        if ($this->option('force')) {
            config()->set('moderation.ai_enabled', true);
        }

        $limit = max(1, min((int) $this->option('limit'), 1000));
        $retried = 0;

        ProductModerationRun::query()
            ->where('provider', '!=', 'local')
            ->where('status', 'failed')
            ->whereIn('id', ProductModerationRun::query()
                ->selectRaw('MAX(id)')
                ->where('provider', '!=', 'local')
                ->where('status', 'failed')
                ->groupBy('product_id'))
            ->latest('id')
            ->limit($limit)
            ->get()
            ->each(function (ProductModerationRun $run) use ($retryService, &$retried): void {
                $product = Product::query()->find($run->product_id);

                if ($product === null || $product->moderation_version !== $run->content_version) {
                    return;
                }

                if ($retryService->retry($product)) {
                    $retried++;
                }
            });

        $this->info("Dispatched {$retried} failed moderation checks.");

        return self::SUCCESS;
    }
}
