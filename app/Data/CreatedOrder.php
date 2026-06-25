<?php

namespace App\Data;

use App\Models\Order;

final readonly class CreatedOrder
{
    public function __construct(
        public Order $order,
        public string $accessToken,
        public bool $wasAlreadyCreated,
    ) {}
}
