<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use RuntimeException;

class ShopService
{
    public const BASE_PACKAGE_ID = 1;

    public function createShop(User $user, array $data, ?UploadedFile $logoFile = null): Seller
    {
        unset($data['logo']);

        $seller = DB::transaction(function () use ($user, $data) {
            $basePackage = Package::find(self::BASE_PACKAGE_ID);

            if (!$basePackage) {
                throw new RuntimeException('Базовый тариф не найден');
            }

            $seller = Seller::create(array_merge($data, [
                'user_id' => $user->id,
                'alias' => $this->generateUniqueAlias($data['name']),
            ]));

            $seller->sellerPackages()->create([
                'package_id' => $basePackage->id,
                'start_date' => now(),
                'end_date' => null,
                'is_active' => true,
            ]);

            return $seller;
        });

        if ($logoFile) {
            $seller->update(['photo' => $this->storeLogo($seller, $logoFile)]);
        }

        return $seller->refresh();
    }

    public function generateUniqueAlias(string $name): string
    {
        $baseAlias = Str::slug($name);
        $baseAlias = $baseAlias ? Str::limit($baseAlias, 240, '') : Str::lower(Str::random(6));

        $alias = $baseAlias;
        $suffixLength = 4;

        while (Seller::where('alias', $alias)->exists()) {
            $alias = $baseAlias . '-' . Str::lower(Str::random($suffixLength));
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
