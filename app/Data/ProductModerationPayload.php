<?php

namespace App\Data;

class ProductModerationPayload
{
    /**
     * @param  array<int, string>  $features
     * @param  array<int, string>  $imagePaths
     */
    public function __construct(
        public readonly int $productId,
        public readonly int $contentVersion,
        public readonly string $fingerprint,
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $features,
        public readonly int $categoryId,
        public readonly string $categoryName,
        public readonly array $imagePaths
    ) {
    }
}
