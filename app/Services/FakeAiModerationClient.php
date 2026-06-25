<?php

namespace App\Services;

use App\Contracts\AiModerationClient;
use App\Data\ProductModerationPayload;
use App\Data\ProductModerationResult;

class FakeAiModerationClient implements AiModerationClient
{
    public function moderate(ProductModerationPayload $payload): ProductModerationResult
    {
        $decision = (string) config('moderation.fake_decision', 'review');

        return new ProductModerationResult(
            decision: $decision,
            riskScore: match ($decision) {
                'allow' => 0.05,
                'block' => 0.95,
                default => 0.6,
            },
            violations: [],
            sellerMessage: $decision === 'allow'
                ? null
                : 'Товар направлен на ручную проверку.',
            providerRequestId: 'fake-'.$payload->fingerprint,
        );
    }
}
