<?php

namespace App\Policies;

use App\Models\Seller;
use App\Models\User;

class SellerPolicy
{
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

    private function ownsSeller(User $user, Seller $seller): bool
    {
        return $seller->user_id === $user->id;
    }
}
