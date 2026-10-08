<?php

namespace App\Data;

use App\Enums\ProductModerationStatus;
use App\Models\Product;

/** Что на самом деле произошло при сохранении товара: по этому строится сообщение продавцу. */
final readonly class ProductWriteOutcome
{
    public function __construct(
        public Product $product,
        /** Товар ушёл на проверку именно в этом сохранении. */
        public bool $submitted,
        /** Изменено то, что проверяется модерацией (название, описание, характеристики, категория, фото). */
        public bool $contentChanged,
        /** Состояние до сохранения. */
        public ProductModerationStatus $statusBefore,
        public bool $wasPublic,
    ) {}
}
