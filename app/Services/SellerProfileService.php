<?php

namespace App\Services;

use App\Models\Seller;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class SellerProfileService
{
    public function updateProfile(Seller $seller, array $data, ?UploadedFile $logoFile = null): Seller
    {
        unset($data['logo']);

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
        $filename = 'logo_' . Str::uuid() . '.webp';
        $path = 'sellers/' . $seller->id;

        $image = Image::make($logoFile)
            ->orientate()
            ->fit(400, 400, function ($constraint) {
                $constraint->upsize();
            })
            ->encode('webp', 85);

        Storage::disk('public')->put($path . '/' . $filename, (string) $image);

        return $path . '/' . $filename;
    }
}
