<?php

namespace App\Data;

final readonly class OrderCreationData
{
    public function __construct(
        public string $customerName,
        public string $customerPhone,
        public int $quantity,
        public string $fulfillmentMethod,
        public ?string $deliveryAddress,
        public ?string $customerComment,
        public string $consentVersion,
        public string $idempotencyKey,
        public ?string $sourceIp = null,
    ) {}
}
