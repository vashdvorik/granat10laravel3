<?php

namespace App\Contracts;

use App\Data\ProductModerationPayload;
use App\Data\ProductModerationResult;

interface ProductImageModerationClient
{
    public function moderate(ProductModerationPayload $payload): ProductModerationResult;
}
