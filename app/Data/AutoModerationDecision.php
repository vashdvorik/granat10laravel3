<?php

namespace App\Data;

class AutoModerationDecision
{
    public function __construct(
        public readonly bool $approve,
        public readonly string $reason
    ) {
    }
}
