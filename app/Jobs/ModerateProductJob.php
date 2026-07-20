<?php

namespace App\Jobs;

use App\Contracts\AiModerationClient;
use App\Enums\ProductModerationStatus;
use App\Exceptions\AiModerationProviderException;
use App\Models\Product;
use App\Models\ProductModerationRun;
use App\Services\AiModerationOutcomeStatusResolver;
use App\Services\ModerationClientResolver;
use App\Services\ModerationProviderMetadataResolver;
use App\Services\ProductAutoApprovalPolicy;
use App\Services\ProductModerationFeedbackService;
use App\Services\ProductModerationPayloadFactory;
use App\Services\ProductModerationScopeFilter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModerateProductJob implements ShouldBeUnique, ShouldQueue
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
        public readonly ?string $endpointPath = null,
        public readonly ?string $mode = null,
        public readonly ?string $configurationVersion = null,
        public readonly bool $forceRetry = false,
    ) {
        $this->onQueue((string) config('moderation.queue', 'moderation'));
        $this->timeout = (int) config('moderation.timeout', 60);
        $this->tries = (int) config('moderation.max_attempts', 3);
    }

    public function uniqueId(): string
    {
        $configuration = $this->configurationVersion ?? hash('sha256', implode('|', [
            $this->provider ?? (string) config('moderation.provider'),
            $this->model ?? '',
            $this->baseUrl ?? '',
            $this->endpointPath ?? '',
            $this->mode ?? (string) config('moderation.mode'),
        ]));

        return $this->productId.':'.$this->contentVersion.':'.$configuration;
    }

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(
        AiModerationClient $client,
        ProductModerationPayloadFactory $payloadFactory,
        ProductAutoApprovalPolicy $autoApprovalPolicy,
        ?ProductModerationFeedbackService $feedbackService = null,
        ?ModerationProviderMetadataResolver $metadataResolver = null,
        ?AiModerationOutcomeStatusResolver $statusResolver = null,
        ?ProductModerationScopeFilter $scopeFilter = null,
        ?ModerationClientResolver $clientResolver = null,
    ): void {
        $feedbackService ??= app(ProductModerationFeedbackService::class);
        $metadataResolver ??= app(ModerationProviderMetadataResolver::class);
        $statusResolver ??= app(AiModerationOutcomeStatusResolver::class);
        $scopeFilter ??= app(ProductModerationScopeFilter::class);
        $product = Product::query()->find($this->productId);

        if (
            $product === null
            || $product->moderation_version !== $this->contentVersion
            || ! in_array($product->moderation_status, [
                ProductModerationStatus::Pending,
                ProductModerationStatus::NeedsReview,
                ProductModerationStatus::Processing,
            ], true)
        ) {
            return;
        }

        $provider = $this->provider ?? (string) config('moderation.provider');
        $model = $this->model ?? match ($provider) {
            'google' => (string) config('moderation.google.model'),
            'openai_compatible' => (string) config('moderation.openai_compatible.model'),
            'deepseek' => (string) config('moderation.deepseek.model'),
            default => 'fake',
        };
        config()->set('moderation.provider', $provider);
        if ($provider !== 'fake') {
            config()->set("moderation.{$provider}.model", $model);
            if ($this->baseUrl !== null) {
                config()->set("moderation.{$provider}.base_url", $this->baseUrl);
            }
            if (in_array($provider, ['openai_compatible', 'deepseek'], true) && $this->endpointPath !== null) {
                config()->set("moderation.{$provider}.chat_completions_path", $this->endpointPath);
            }
        }
        if ($this->provider !== null) {
            $client = ($clientResolver ?? app(ModerationClientResolver::class))->text($provider);
        }
        $payload = $payloadFactory->make($product);
        $metadata = $metadataResolver->resolve();
        $runIdentity = [
            'product_id' => $product->id,
            'content_version' => $this->contentVersion,
            'request_fingerprint' => $payload->fingerprint,
            'provider' => $metadata->provider,
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
                'configuration_version' => $this->configurationVersion ?? $this->configurationHash($provider, $model),
                'trigger' => 'queue',
                'model' => $metadata->model,
                'prompt_version' => $metadata->promptVersion,
                'status' => 'processing',
                'started_at' => now(),
                'text_result' => ['policy_version' => $metadata->policyVersion],
            ]);
        } else {
            $run->update(['status' => 'processing', 'started_at' => now()]);
        }

        $shadowMode = ($this->mode ?? config('moderation.mode')) === 'shadow';

        if (! $shadowMode) {
            $product->forceFill([
                'moderation_status' => ProductModerationStatus::Processing,
                'moderation_source' => 'ai',
            ])->save();
        }

        $startedAt = hrtime(true);

        try {
            $result = $client->moderate($payload);
            $result = $scopeFilter->filter($result);
        } catch (Throwable $exception) {
            Log::channel('moderation')->warning('AI moderation request failed.', [
                'product_id' => $this->productId,
                'content_version' => $this->contentVersion,
                'provider' => (string) config('moderation.provider'),
                'attempt' => $this->attempts(),
                'exception' => $exception::class,
                'retryable' => ! $exception instanceof AiModerationProviderException
                    || $exception->isRetryable(),
            ]);

            $run->update([
                'status' => 'failed',
                'error_code' => $exception::class,
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                'finished_at' => now(),
            ]);

            $retryable = ! $exception instanceof AiModerationProviderException
                || $exception->isRetryable();

            if (! $shadowMode) {
                $shouldMoveToReview = ! $retryable || config('queue.default') === 'sync';
                $product->forceFill([
                    'moderation_status' => $shouldMoveToReview
                        ? ProductModerationStatus::NeedsReview
                        : ProductModerationStatus::Processing,
                    'moderation_summary' => $shouldMoveToReview
                        ? 'Автоматическая проверка временно недоступна. Товар передан администратору на проверку.'
                        : null,
                    'moderated_version' => null,
                    'is_active' => false,
                ])->save();
            }

            if (! $retryable || config('queue.default') === 'sync') {
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

        $autoDecision = $autoApprovalPolicy->evaluate($freshProduct, $result);
        $waitingForImages = $autoApprovalPolicy->shouldWaitForImageModeration($freshProduct, $autoDecision);

        if (! $shadowMode) {
            $status = $waitingForImages
                ? ProductModerationStatus::Processing
                : $statusResolver->resolve($result, $autoDecision);

            $freshProduct->forceFill([
                'moderation_status' => $status,
                'is_active' => $status === ProductModerationStatus::Approved
                    && $freshProduct->publication_requested,
                'moderated_version' => $status === ProductModerationStatus::Approved
                    ? $this->contentVersion
                    : null,
                'moderation_source' => 'ai',
                'moderation_risk_score' => $result->riskScore,
                'moderation_summary' => $waitingForImages
                    ? null
                    : ($autoDecision->approve
                    ? null
                    : $feedbackService->forSeller($result, $autoDecision->reason)),
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
            'text_result' => [
                'decision' => $result->decision,
                'seller_message' => $result->sellerMessage,
                'auto_approved' => $shadowMode ? false : $autoDecision->approve,
                'auto_approval_reason' => $shadowMode ? 'Shadow mode.' : $autoDecision->reason,
                'policy_version' => $metadata->policyVersion,
            ],
            'image_result' => [
                'images_submitted' => count($payload->imagePaths),
                'images_analyzed' => $result->imagesAnalyzed,
            ],
            'provider_request_id' => $result->providerRequestId,
            'latency_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
            'finished_at' => now(),
        ]);

        Log::channel('moderation')->info('AI moderation completed.', [
            'product_id' => $this->productId,
            'content_version' => $this->contentVersion,
            'provider' => (string) config('moderation.provider'),
            'decision' => $result->decision,
            'risk_score' => $result->riskScore,
            'latency_ms' => (int) round((hrtime(true) - $startedAt) / 1_000_000),
        ]);

        if (($waitingForImages ?? false) && config('moderation.images.enabled', false)) {
            $imageProvider = (string) config('moderation.images.provider', 'google');
            $imageModel = (string) config("moderation.images.{$imageProvider}.model");
            ModerateProductImagesJob::dispatch(
                $this->productId,
                $this->contentVersion,
                $imageProvider,
                $imageModel,
                (string) config("moderation.images.{$imageProvider}.base_url"),
                $this->mode ?? (string) config('moderation.mode'),
                $this->configurationHash($imageProvider, $imageModel, 'image'),
            )->afterCommit();
        }
    }

    private function configurationHash(string $provider, string $model, string $channel = 'text'): string
    {
        return hash('sha256', implode('|', [
            $channel,
            $provider,
            $model,
            $this->baseUrl ?? '',
            $this->endpointPath ?? '',
            $this->mode ?? (string) config('moderation.mode'),
            (string) config('moderation.audit.policy_version'),
            (string) config('moderation.images.audit.policy_version'),
        ]));
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('moderation')->error('AI moderation job exhausted all attempts.', [
            'product_id' => $this->productId,
            'content_version' => $this->contentVersion,
            'provider' => (string) config('moderation.provider'),
            'exception' => $exception?->getMessage() === null ? null : $exception::class,
        ]);

        if (($this->mode ?? config('moderation.mode')) === 'shadow') {
            return;
        }

        $product = Product::query()->find($this->productId);

        if ($product !== null && $product->moderation_version === $this->contentVersion) {
            $product->forceFill([
                'moderation_status' => ProductModerationStatus::NeedsReview,
                'moderation_summary' => 'Автоматическая проверка временно недоступна. Товар передан администратору на проверку.',
                'is_active' => false,
            ])->save();
        }
    }
}
