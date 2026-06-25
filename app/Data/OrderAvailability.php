<?php

namespace App\Data;

final readonly class OrderAvailability
{
    public function __construct(
        public bool $allowed,
        public bool $deliveryAllowed,
        public ?string $reason = null,
    ) {}
}
