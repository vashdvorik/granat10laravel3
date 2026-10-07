<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Application\Products\UpdateProduct as UpdateProductAction;
use App\Enums\ProductSaveAction;
use App\Filament\Resources\ProductResource;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductVipService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $images = $data['images'] ?? [];
        $vip = [
            'is_vip' => (bool) ($data['is_vip'] ?? false),
            'vip_until' => $data['vip_until'] ?? null,
        ];
        unset($data['is_vip'], $data['vip_until']);
        $data['save_action'] = ! empty($data['is_active'])
            ? ProductSaveAction::Submit->value
            : ProductSaveAction::Draft->value;
        unset($data['is_active']);

        $request = new UpdateProductRequest;
        $validated = Validator::make($data, $request->rules(), $request->messages())->validate();

        $product = app(UpdateProductAction::class)->execute(
            $record,
            auth()->user(),
            $validated,
            $images,
        )->product;

        return app(ProductVipService::class)->setByAdmin($product, $vip['is_vip'], $vip['vip_until']);
    }
}
