<?php

namespace App\Exceptions;

use RuntimeException;

class AiModerationProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly bool $retryable = false,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, previous: $previous);
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }
}
