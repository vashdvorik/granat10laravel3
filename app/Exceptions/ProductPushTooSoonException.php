<?php

namespace App\Exceptions;

use Carbon\CarbonInterface;
use RuntimeException;

class ProductPushTooSoonException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly CarbonInterface $availableAt
    ) {
        parent::__construct($message);
    }
}
