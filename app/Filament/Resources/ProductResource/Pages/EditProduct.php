<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductWriteService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $images = $data['images'] ?? [];

        $request = new UpdateProductRequest;
        $validated = Validator::make($data, $request->rules(), $request->messages())->validate();

        return app(ProductWriteService::class)->update(
            $record,
            $validated,
            $images,
        );
    }
}
