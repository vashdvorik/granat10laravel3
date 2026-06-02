<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Политика авторизации для продуктов
 */
class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Может ли пользователь просматривать список продуктов
     */
    public function viewAny(?User $user): bool
    {
        // Все могут просматривать список
        return true;
    }

    /**
     * Может ли пользователь просматривать продукт
     */
    public function view(?User $user, Product $product): bool
    {
        // Все могут просматривать активные продукты с активным пакетом
        if ($product->is_active && $product->seller->hasActivePackage()) {
            return true;
        }

        // Владелец может видеть свои неактивные продукты
        return $user && $user->id === $product->user_id;
    }

    /**
     * Может ли пользователь создавать продукты
     */
    public function create(User $user): bool
    {
        // Только пользователи с магазином могут создавать продукты
        return $user->seller !== null;
    }

    /**
     * Может ли пользователь редактировать продукт
     */
    public function update(User $user, Product $product): bool
    {
        // Только владелец продукта может его редактировать
        return $user->id === $product->user_id;
    }

    /**
     * Может ли пользователь удалять продукт
     */
    public function delete(User $user, Product $product): bool
    {
        // Только владелец продукта может его удалять
        return $user->id === $product->user_id;
    }

    /**
     * Может ли пользователь восстанавливать продукт
     */
    public function restore(User $user, Product $product): bool
    {
        // Только владелец продукта может его восстанавливать
        return $user->id === $product->user_id;
    }

    /**
     * Может ли пользователь окончательно удалять продукт
     */
    public function forceDelete(User $user, Product $product): bool
    {
        // Только владелец продукта может его окончательно удалять
        return $user->id === $product->user_id;
    }

    /**
     * Может ли пользователь активировать/деактивировать продукт
     */
    public function toggleActive(User $user, Product $product): bool
    {
        // Только владелец продукта может менять статус активности
        if ($user->id !== $product->user_id) {
            return false;
        }

        // Если активируем - проверяем лимиты пакета
        if (! $product->is_active) {
            $result = $user->seller->canActivateProduct();
            return $result['allowed'];
        }

        return true;
    }

    /**
     * Может ли пользователь сделать продукт VIP
     */
    public function makeVip(User $user, Product $product): bool
    {
        // Только владелец продукта
        if ($user->id !== $product->user_id) {
            return false;
        }

        // Проверяем лимиты VIP продуктов
        $result = $user->seller->canActivateVipProduct();
        return $result['allowed'];
    }
}
