<?php

namespace App\Application\Products;

use App\Data\ProductWriteResult;
use App\Enums\ProductSaveAction;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductWriteService;
use Illuminate\Http\UploadedFile;

class UpdateProduct
{
    public function __construct(
        private readonly ProductWriteService $productWriteService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function execute(
        Product $product,
        User $actor,
        array $data,
        array $images = []
    ): ProductWriteResult {
        $action = ProductSaveAction::from($data['save_action']);
        $outcome = $this->productWriteService->updateWithOutcome($product, $data, $images, $actor);

        return new ProductWriteResult(
            product: $outcome->product,
            action: $action,
            moderationDispatched: $outcome->submitted,
            userMessage: ProductSaveMessage::forUpdate($outcome),
        );
    }
}
