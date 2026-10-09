<?php

namespace App\Policies;

use App\Models\Seller;
use App\Models\User;

class SellerPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasPermission('browse_admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function view(User $user, Seller $seller): bool
    {
        return $this->ownsSeller($user, $seller);
    }

    public function update(User $user, Seller $seller): bool
    {
        return $this->ownsSeller($user, $seller);
    }

    public function delete(User $user, Seller $seller): bool
    {
        // Скрытый администратором магазин владелец удалить не может: иначе причина скрытия исчезнет вместе с ним
        return $this->ownsSeller($user, $seller) && ! $seller->isHidden();
    }

    /** Скрыть магазин и вернуть его на сайт может только администратор (доступ даёт before()). */
    public function manageVisibility(User $user, Seller $seller): bool
    {
        return false;
    }

    public function manageSettings(User $user, Seller $seller): bool
    {
        return $this->ownsSeller($user, $seller);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function ownsSeller(User $user, Seller $seller): bool
    {
        return $seller->user_id === $user->id;
    }
}
