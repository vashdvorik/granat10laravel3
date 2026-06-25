<?php

namespace App\Application\Products;

use App\Data\ProductWriteResult;
use App\Enums\ProductSaveAction;
use App\Models\Seller;
use App\Models\User;
use App\Services\ProductWriteService;
use Illuminate\Http\UploadedFile;

class CreateProduct
{
    public function __construct(
        private readonly ProductWriteService $productWriteService
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function execute(
        Seller $seller,
        User $actor,
        array $data,
        array $images = []
    ): ProductWriteResult {
        $action = ProductSaveAction::from($data['save_action']);
        $product = $this->productWriteService->create($seller, $data, $images, $actor);

        return new ProductWriteResult(
            product: $product,
            action: $action,
            moderationDispatched: $action->requestsModeration(),
            userMessage: $action === ProductSaveAction::Draft
                ? 'Товар сохранён как черновик.'
                : 'Товар сохранён и отправлен на проверку.',
        );
    }
}
