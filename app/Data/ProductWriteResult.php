<?php

namespace App\Data;

use App\Enums\ProductSaveAction;
use App\Models\Product;

final readonly class ProductWriteResult
{
    public function __construct(
        public Product $product,
        public ProductSaveAction $action,
        public bool $moderationDispatched,
        public string $userMessage,
    ) {}
}
