<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для работы с магазинами продавцов
 */
class SellerService
{
    /**
     * Создание магазина для пользователя
     */
    public function createSeller(User $user, array $data): Seller
    {
        return DB::transaction(function () use ($user, $data) {
            $seller = new Seller;
            $seller->fill($data);
            $seller->user_id = $user->id;
            $seller->is_active = true;
            $seller->save();

            return $seller;
        });
    }

    /**
     * Обновление данных магазина
     */
    public function updateSeller(Seller $seller, array $data): Seller
    {
        $seller->fill($data);
        $seller->save();

        // Очистка кэша магазина
        $this->clearSellerCache($seller);

        return $seller->fresh();
    }

    /**
     * Получение статистики магазина
     */
    public function getSellerStats(Seller $seller): array
    {
        return Cache::remember(
            "seller.{$seller->id}.stats",
            now()->addHours(1),
            fn () => [
                'total_products' => $seller->products()->count(),
                'active_products' => $seller->products()->where('is_active', true)->count(),
                'vip_products' => $seller->products()->where('is_vip', true)->count(),
                'total_views' => $seller->products()->sum('views_count'),
                'has_active_package' => $seller->hasActivePackage(),
            ]
        );
    }

    /**
     * Проверка уникальности slug магазина
     */
    public function isSlugUnique(string $slug, ?int $excludeSellerId = null): bool
    {
        $query = Seller::where('slug', $slug);

        if ($excludeSellerId) {
            $query->where('id', '!=', $excludeSellerId);
        }

        return ! $query->exists();
    }

    /**
     * Очистка кэша магазина
     */
    private function clearSellerCache(Seller $seller): void
    {
        Cache::forget("seller.{$seller->id}");
        Cache::forget("seller.{$seller->id}.stats");
        Cache::forget("seller.{$seller->id}.products");
    }
}
