<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Seller;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Политика авторизации для магазинов
 */
class SellerPolicy
{
    use HandlesAuthorization;

    /**
     * Может ли пользователь просматривать магазин
     */
    public function view(?User $user, Seller $seller): bool
    {
        // Все могут просматривать активные магазины
        if ($seller->is_active) {
            return true;
        }

        // Владелец может видеть свой неактивный магазин
        return $user && $user->id === $seller->user_id;
    }

    /**
     * Может ли пользователь редактировать магазин
     */
    public function update(User $user, Seller $seller): bool
    {
        // Только владелец магазина может его редактировать
        return $user->id === $seller->user_id;
    }

    /**
     * Может ли пользователь удалять магазин
     */
    public function delete(User $user, Seller $seller): bool
    {
        // Только владелец магазина может его удалять
        return $user->id === $seller->user_id;
    }

    /**
     * Может ли пользователь управлять пакетами магазина
     */
    public function managePackages(User $user, Seller $seller): bool
    {
        // Только владелец магазина может управлять пакетами
        return $user->id === $seller->user_id;
    }

    /**
     * Может ли пользователь управлять продуктами магазина
     */
    public function manageProducts(User $user, Seller $seller): bool
    {
        // Только владелец магазина может управлять продуктами
        return $user->id === $seller->user_id;
    }
}
