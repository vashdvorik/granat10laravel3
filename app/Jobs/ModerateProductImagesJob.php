<?php

namespace App\Jobs;

use App\Contracts\ProductImageModerationClient;
use App\Enums\ProductModerationStatus;
use App\Exceptions\AiModerationProviderException;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Services\AiModerationOutcomeStatusResolver;
use App\Services\LocalImageContentScanner;
use App\Services\ModerationClientResolver;
use App\Services\ProductAutoApprovalPolicy;
use App\Services\ProductModerationFeedbackService;
use App\Services\ProductModerationPayloadFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModerateProductImagesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;

    public int $tries;

    public int $uniqueFor = 600;

    public function __construct(
        public readonly int $productId,
        public readonly int $contentVersion,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
        public readonly ?string $baseUrl = null,
        public readonly ?string $mode = null,
        public readonly ?string $configurationVersion = null,
        public readonly bool $forceRetry = false,
    ) {
        $this->onQueue((string) config('moderation.images.queue', 'moderation'));
        $this->timeout = (int) config('moderation.images.timeout', 60);
        $this->tries = (int) config('moderation.images.max_attempts', 3);
    }

    public function uniqueId(): string
    {
        $configuration = $this->configurationVersion ?? hash('sha256', implode('|', [
            $this->provider ?? (string) config('moderation.images.provider'),
            $this->model ?? '',
            $this->baseUrl ?? '',
            $this->mode ?? (string) config('moderation.mode'),
        ]));

        return 'images:'.$this->productId.':'.$this->contentVersion.':'.$configuration;
    }

    private function imageRequestFingerprint(string $fingerprint): string
    {
        return hash('sha256', $fingerprint);
    }

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(
        ProductImageModerationClient $client,
        ProductModerationPayloadFactory $payloadFactory,
        ProductAutoApprovalPolicy $autoApprovalPolicy,
        ?ProductModerationFeedbackService $feedbackService = null,
        ?AiModerationOutcomeStatusResolver $statusResolver = null,
        ?ModerationClientResolver $clientResolver = null,
        ?LocalImageContentScanner $localScanner = null,
    ): void {
        $feedbackService ??= app(ProductModerationFeedbackService::class);
        $statusResolver ??= app(AiModerationOutcomeStatusResolver::class);
        $localScanner ??= app(LocalImageContentScanner::class);
        $product = Product::query()->find($this->productId);

        if (
            $product === null
            || $product->moderation_version !== $this->contentVersion
            || ! in_array($product->moderation_status, [
                ProductModerationStatus::Processing,
                ProductModerationStatus::Pending,
                ProductModerationStatus::NeedsReview,
            ], true)
        ) {
            return;
        }

        $imageProvider = $this->provider ?? (string) config('moderation.images.provider', 'google');
        $imageModel = $this->model ?? (string) config("moderation.images.{$imageProvider}.model");
        config()->set('moderation.images.provider', $imageProvider);
        config()->set("moderation.images.{$imageProvider}.model", $imageModel);
        if ($this->baseUrl !== null) {
            config()->set("moderation.images.{$imageProvider}.base_url", $this->baseUrl);
        }
        if ($this->provider !== null) {
            $client = ($clientResolver ?? app(ModerationClientResolver::class))->images($imageProvider);
        }
        $payload = $payloadFactory->make($product);
        $runIdentity = [
            'product_id' => $product->id,
            'content_version' => $this->contentVersion,
            'request_fingerprint' => $this->imageRequestFingerprint($payload->fingerprint.':images'),
            'provider' => 'image_'.$imageProvider,
        ];

        if (! $this->forceRetry && ProductModerationRun::query()
            ->where($runIdentity)
            ->where('status', 'completed')
            ->exists()) {
            return;
        }

        $run = ProductModerationRun::query()->where($runIdentity)->where('status', 'retrying')->latest('attempt_number')->first();

        if ($run === null) {
            $previousRun = ProductModerationRun::query()->where($runIdentity)->latest('attempt_number')->first();
            $attemptNumber = ((int) ($previousRun?->attempt_number ?? 0)) + 1;
            $run = ProductModerationRun::query()->create($runIdentity + [
                'retry_of_id' => $previousRun?->id,
                'attempt_number' => $attemptNumber,
                'configuration_version' => $this->configurationVersion ?? hash('sha256', "image|{$imageProvider}|{$imageModel}"),
                'trigger' => 'image_queue',
                'model' => $imageModel,
                'prompt_version' => (string) config('moderation.images.audit.prompt_version'),
                'status' => 'processing',
                'started_at' => now(),
                'image_result' => [
                    'policy_version' => config('moderation.images.audit.policy_version'),
                    'images_submitted' => count($payload->imagePaths),
                ],
            ]);
        } else {
            $run->update(['status' => 'processing', 'started_at' => now()]);
        }

        $startedAt = hrtime(true);

        try {
            $localScan = $localScanner->scan($payload->imagePaths);
            $result = $localScan['violations'] === []
                ? $client->moderate($payload)
                : new \App\Data\ProductModerationResult(
                    decision: 'review',
                    riskScore: 0.95,
                    violations: $localScan['violations'],
                    sellerMessage: 'Удалите контактные данные, ссылки или QR-коды с изображений товара.',
                    imagesAnalyzed: (bool) $localScan['scanned'],
                );
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'error_code' => $exception::class,
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                'finished_at' => now(),
            ]);

            Log::channel('moderation')->warning('Image moderation request failed.', [
                'product_id' => $this->productId,
                'content_version' => $this->contentVersion,
                'attempt' => $this->attempts(),
                'exception' => $exception::class,
            ]);

            $retryable = ! $exception instanceof AiModerationProviderException
                || $exception->isRetryable();

            if (! $retryable || config('queue.default') === 'sync') {
                if (($this->mode ?? config('moderation.mode')) !== 'shadow') {
                    $product->forceFill([
                        'moderation_status' => ProductModerationStatus::NeedsReview,
                        'moderation_summary' => 'Фото товара переданы администратору на проверку.',
                        'is_active' => false,
                        'moderated_version' => null,
                    ])->save();
                }

                return;
            }

            throw $exception;
        }

        $freshProduct = Product::query()->find($this->productId);

        if ($freshProduct === null || $freshProduct->moderation_version !== $this->contentVersion) {
            $run->update([
                'status' => 'stale',
                'decision' => $result->decision,
                'finished_at' => now(),
            ]);

            return;
        }

        $shadowMode = ($this->mode ?? config('moderation.mode')) === 'shadow';
        $autoDecision = $autoApprovalPolicy->evaluateAfterImageModeration($freshProduct, $result);
        $status = $statusResolver->resolve($result, $autoDecision);

        if (! $shadowMode) {
            $freshProduct->forceFill([
                'moderation_status' => $status,
                'is_active' => $status === ProductModerationStatus::Approved
                    && $freshProduct->publication_requested,
                'moderated_version' => $status === ProductModerationStatus::Approved
                    ? $this->contentVersion
                    : null,
                'moderation_source' => 'ai_image',
                'moderation_risk_score' => $result->riskScore,
                'moderation_summary' => $autoDecision->approve
                    ? null
                    : $feedbackService->forSeller($result, $autoDecision->reason),
                'moderated_at' => now(),
                'moderated_by_user_id' => null,
            ])->save();
        }

        $run->update([
            'status' => 'completed',
            'decision' => $result->decision,
            'risk_score' => $result->riskScore,
            'categories' => collect($result->violations)->pluck('code')->filter()->values()->all(),
            'reasons' => $result->violations,
            'image_result' => [
                'decision' => $result->decision,
                'seller_message' => $result->sellerMessage,
                'auto_approved' => $shadowMode ? false : $autoDecision->approve,
                'auto_approval_reason' => $shadowMode ? 'Shadow mode.' : $autoDecision->reason,
                'policy_version' => config('moderation.images.audit.policy_version'),
                'images_submitted' => count($payload->imagePaths),
                'images_analyzed' => $result->imagesAnalyzed,
                'local_scan_performed' => (bool) ($localScan['scanned'] ?? false),
            ],
            'provider_request_id' => $result->providerRequestId,
            'latency_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            'finished_at' => now(),
        ]);
    }
}
