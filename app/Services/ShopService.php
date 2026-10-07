<?php

namespace App\Services;

use App\Models\Seller;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ShopService
{
    private const MAX_ALIAS_LENGTH = 191;
    private const ALIAS_SUFFIX_RESERVE = 12;

    public function createShop(User $user, array $data, ?UploadedFile $logoFile = null): Seller
    {
        unset($data['logo']);
        $data['town'] = Settlement::query()->findOrFail($data['settlement_id'])->name;

        $seller = Seller::create(array_merge($data, [
            'user_id' => $user->id,
            'alias' => $this->generateUniqueAlias($data['name']),
        ]));

        if ($logoFile) {
            $seller->update(['photo' => $this->storeLogo($seller, $logoFile)]);
        }

        return $seller->refresh();
    }

    public function generateUniqueAlias(string $name): string
    {
        $baseAlias = Str::slug($name);
        $baseAlias = $baseAlias
            ? Str::limit($baseAlias, self::MAX_ALIAS_LENGTH - self::ALIAS_SUFFIX_RESERVE, '')
            : Str::lower(Str::random(6));

        $alias = $baseAlias;
        $suffixLength = 4;

        while (Seller::where('alias', $alias)->exists()) {
            $suffix = '-' . Str::lower(Str::random($suffixLength));
            $alias = Str::limit($baseAlias, self::MAX_ALIAS_LENGTH - strlen($suffix), '') . $suffix;
            $suffixLength++;
        }

        return $alias;
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
