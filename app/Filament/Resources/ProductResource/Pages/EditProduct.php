<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Application\Products\UpdateProduct as UpdateProductAction;
use App\Enums\ProductSaveAction;
use App\Filament\Resources\ProductResource;
use App\Http\Requests\UpdateProductRequest;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $images = $data['images'] ?? [];
        $data['save_action'] = ! empty($data['is_active'])
            ? ProductSaveAction::Submit->value
            : ProductSaveAction::Draft->value;
        unset($data['is_active']);

        $request = new UpdateProductRequest;
        $validated = Validator::make($data, $request->rules(), $request->messages())->validate();

        return app(UpdateProductAction::class)->execute(
            $record,
            auth()->user(),
            $validated,
            $images,
        )->product;
    }
}
