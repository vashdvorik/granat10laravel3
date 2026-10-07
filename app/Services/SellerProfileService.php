<?php

namespace App\Services;

use App\Models\Seller;
use App\Models\Settlement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;

class SellerProfileService
{
    public function updateProfile(Seller $seller, array $data, ?UploadedFile $logoFile = null): Seller
    {
        $data = Arr::only($data, [
            'name',
            'phone',
            'settlement_id',
            'sellerplace',
            'address',
            'description',
            'link',
            'link_map',
            'telegram',
            'instagram',
            'viber',
            'can_order',
            'delivery_enabled',
            'delivery_terms',
        ]);

        $data['can_order'] = (bool) ($data['can_order'] ?? false);
        $data['delivery_enabled'] = (bool) ($data['delivery_enabled'] ?? false);

        if (! $data['can_order']) {
            $data['delivery_enabled'] = false;
        }

        if (isset($data['settlement_id'])) {
            $data['town'] = Settlement::query()->findOrFail($data['settlement_id'])->name;
        }

        if ($logoFile) {
            if ($seller->photo) {
                Storage::disk('public')->delete($seller->photo);
            }

            $data['photo'] = $this->storeLogo($seller, $logoFile);
        }

        $seller->update($data);

        return $seller->refresh();
    }

    private function storeLogo(Seller $seller, UploadedFile $logoFile): string
    {
        $filename = 'logo_'.Str::uuid().'.webp';
        $path = 'sellers/'.$seller->id;

        $image = Image::make($logoFile)
            ->orientate()
            ->fit(400, 400, function ($constraint) {
                $constraint->upsize();
            })
            ->encode('webp', 85);

        Storage::disk('public')->put($path.'/'.$filename, (string) $image);

        return $path.'/'.$filename;
    }
}
