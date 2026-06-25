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
        $updatedProduct = $this->productWriteService->update($product, $data, $images, $actor);

        return new ProductWriteResult(
            product: $updatedProduct,
            action: $action,
            moderationDispatched: $action->requestsModeration(),
            userMessage: match ($action) {
                ProductSaveAction::Draft => 'Изменения сохранены в черновике.',
                ProductSaveAction::Submit => 'Изменения сохранены и отправлены на проверку.',
                ProductSaveAction::Save => 'Изменения сохранены.',
            },
        );
    }
}
