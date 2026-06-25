<?php

namespace App\Exceptions;

class AiModerationBudgetExceededException extends AiModerationProviderException
{
    public function __construct(string $period)
    {
        parent::__construct(
            "AI moderation {$period} request budget has been exhausted.",
            retryable: false,
        );
    }
}
