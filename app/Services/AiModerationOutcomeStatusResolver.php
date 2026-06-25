<?php

namespace App\Services;

use App\Data\AutoModerationDecision;
use App\Data\ProductModerationResult;
use App\Enums\ProductModerationStatus;

class AiModerationOutcomeStatusResolver
{
    public function resolve(
        ProductModerationResult $result,
        AutoModerationDecision $autoDecision,
    ): ProductModerationStatus {
        if ($autoDecision->approve) {
            return ProductModerationStatus::Approved;
        }

        if ($this->hasActionableFeedback($result)) {
            return ProductModerationStatus::ChangesRequired;
        }

        return ProductModerationStatus::NeedsReview;
    }

    private function hasActionableFeedback(ProductModerationResult $result): bool
    {
        return collect($result->violations)->contains(
            fn (mixed $violation): bool => is_array($violation)
                && (
                    filled($violation['evidence'] ?? null)
                    || filled($violation['suggestion'] ?? null)
                )
        );
    }
}
