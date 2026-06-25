<?php

namespace App\Data;

use InvalidArgumentException;

class ProductModerationResult
{
    /**
     * @param  array<int, array<string, mixed>>  $violations
     */
    public function __construct(
        public readonly string $decision,
        public readonly float $riskScore,
        public readonly array $violations = [],
        public readonly ?string $sellerMessage = null,
        public readonly ?string $providerRequestId = null,
        public readonly bool $imagesAnalyzed = false,
    ) {
        if (! in_array($decision, ['allow', 'review', 'block'], true)) {
            throw new InvalidArgumentException('Unsupported moderation decision.');
        }

        if ($riskScore < 0 || $riskScore > 1) {
            throw new InvalidArgumentException('Risk score must be between 0 and 1.');
        }
    }
}
