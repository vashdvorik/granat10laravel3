<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Http\Requests\StoreProductRequest;
use App\Models\Seller;
use App\Services\ProductWriteService;
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

        unset($data['seller_id']);

        $request = new StoreProductRequest;
        $validated = Validator::make($data, $request->rules(), $request->messages())->validate();

        return app(ProductWriteService::class)->create(
            $seller,
            $validated,
            $images,
        );
    }
}
