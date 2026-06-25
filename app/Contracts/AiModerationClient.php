<?php

namespace App\Contracts;

use App\Data\ProductModerationPayload;
use App\Data\ProductModerationResult;

interface AiModerationClient
{
    public function moderate(ProductModerationPayload $payload): ProductModerationResult;
}
