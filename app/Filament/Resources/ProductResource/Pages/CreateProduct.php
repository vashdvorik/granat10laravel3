<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Application\Products\CreateProduct as CreateProductAction;
use App\Enums\ProductSaveAction;
use App\Filament\Resources\ProductResource;
use App\Http\Requests\StoreProductRequest;
use App\Models\Seller;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $seller = Seller::query()->findOrFail($data['seller_id']);
        $images = $data['images'] ?? [];
        $data['save_action'] = ! empty($data['is_active'])
            ? ProductSaveAction::Submit->value
            : ProductSaveAction::Draft->value;

        unset($data['seller_id'], $data['is_active']);

        $request = new StoreProductRequest;
        $validated = Validator::make($data, $request->rules(), $request->messages())->validate();

        return app(CreateProductAction::class)->execute(
            $seller,
            auth()->user(),
            $validated,
            $images,
        )->product;
    }
}
