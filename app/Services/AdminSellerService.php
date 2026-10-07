<?php

namespace App\Services;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AdminSellerService
{
    public function delete(User $actor, Seller $seller): string
    {
        Gate::forUser($actor)->authorize('delete', $seller);

        [$sellerName, $filePaths] = DB::transaction(function () use ($actor, $seller): array {
            $lockedSeller = Seller::query()
                ->with('products.images')
                ->lockForUpdate()
                ->findOrFail($seller->getKey());

            $filePaths = $lockedSeller->products
                ->flatMap->images
                ->pluck('path')
                ->filter()
                ->push($lockedSeller->photo)
                ->merge($this->legacyPhotoPaths($lockedSeller->photos))
                ->filter()
                ->unique()
                ->values()
                ->all();

            activity('sellers')
                ->causedBy($actor)
                ->performedOn($lockedSeller)
                ->withProperties([
                    'seller_id' => $lockedSeller->getKey(),
                    'seller_name' => $lockedSeller->name,
                    'owner_id' => $lockedSeller->user_id,
                ])
                ->log('Административное удаление магазина');

            foreach ($lockedSeller->products as $product) {
                $product->images()->delete();
                $product->delete();
            }

            $sellerName = $lockedSeller->name;
            $lockedSeller->delete();

            return [$sellerName, $filePaths];
        });

        if ($filePaths !== []) {
            Storage::disk('public')->delete($filePaths);
        }

        return $sellerName;
    }

    /**
     * @return array<int, string>
     */
    private function legacyPhotoPaths(mixed $photos): array
    {
        if (! is_string($photos) || trim($photos) === '') {
            return [];
        }

        $decoded = json_decode($photos, true);

        if (is_array($decoded)) {
            return array_values(array_filter($decoded, 'is_string'));
        }

        return [$photos];
    }
}
