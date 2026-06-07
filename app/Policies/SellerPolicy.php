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
        return $this->ownsSeller($user, $seller);
    }

    public function manageSettings(User $user, Seller $seller): bool
    {
        return $this->ownsSeller($user, $seller);
    }

    public function purchasePackage(User $user, Seller $seller): bool
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
