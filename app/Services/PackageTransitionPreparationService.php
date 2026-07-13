<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Seller;
use App\Models\SellerPackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackageTransitionPreparationService
{
    public function __construct(
        private readonly PackageService $packageService
    ) {}

    public function activeProducts(Seller $seller): Collection
    {
        return $seller->products()
            ->with(['category', 'images'])
            ->where('is_active', true)
            ->orderByDesc('is_vip')
            ->orderByDesc('created_at')
            ->get();
    }

    public function apply(
        User $user,
        Seller $seller,
        Package $package,
        array $activeProductIds,
        array $vipProductIds,
    ): SellerPackage {
        return DB::transaction(function () use ($user, $seller, $package, $activeProductIds, $vipProductIds): SellerPackage {
            $activeProducts = $seller->products()
                ->where('is_active', true)
                ->lockForUpdate()
                ->get(['id', 'is_vip']);

            $activeProductIds = $this->normalizeIds($activeProductIds);
            $vipProductIds = $this->normalizeIds($vipProductIds);
            $currentActiveIds = $activeProducts->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
            $currentVipIds = $activeProducts
                ->where('is_vip', true)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $this->ensureSelectedProductsBelongToSeller($activeProductIds, $currentActiveIds, 'active_product_ids');
            $this->ensureSelectedProductsBelongToSeller($vipProductIds, $currentVipIds, 'vip_product_ids');
            $vipProductIds = array_values(array_intersect($vipProductIds, $activeProductIds));
            $this->ensureActiveLimit($package, count($activeProductIds));
            $this->ensureVipLimit($package, count($vipProductIds));

            if ($package->active_products_limit !== null) {
                $seller->products()
                    ->where('is_active', true)
                    ->whereNotIn('id', $activeProductIds)
                    ->update([
                        'is_active' => false,
                        'publication_requested' => false,
                    ]);
            }

            if ($package->vip_products_limit !== null) {
                $seller->products()
                    ->where('is_active', true)
                    ->where('is_vip', true)
                    ->whereNotIn('id', $vipProductIds)
                    ->update(['is_vip' => false]);
            }

            return $this->packageService->purchase($user, $seller->refresh(), $package);
        });
    }

    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function ensureSelectedProductsBelongToSeller(array $selectedIds, array $allowedIds, string $field): void
    {
        if (array_diff($selectedIds, $allowedIds) !== []) {
            throw ValidationException::withMessages([
                $field => 'Выбран товар, который не относится к активным товарам этого магазина.',
            ]);
        }
    }

    private function ensureActiveLimit(Package $package, int $selectedCount): void
    {
        if ($package->active_products_limit !== null && $selectedCount > $package->active_products_limit) {
            throw ValidationException::withMessages([
                'active_product_ids' => "Тариф разрешает не более {$package->active_products_limit} активных товаров.",
            ]);
        }
    }

    private function ensureVipLimit(Package $package, int $selectedCount): void
    {
        if ($package->vip_products_limit !== null && $selectedCount > $package->vip_products_limit) {
            throw ValidationException::withMessages([
                'vip_product_ids' => "Тариф разрешает не более {$package->vip_products_limit} VIP-товаров.",
            ]);
        }
    }
}
