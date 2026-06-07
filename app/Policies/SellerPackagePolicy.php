<?php

namespace App\Policies;

use App\Models\SellerPackage;
use App\Models\User;

class SellerPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function view(User $user, SellerPackage $sellerPackage): bool
    {
        return $this->isAdministrator($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function update(User $user, SellerPackage $sellerPackage): bool
    {
        return $this->isAdministrator($user);
    }

    public function delete(User $user, SellerPackage $sellerPackage): bool
    {
        return $this->isAdministrator($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    private function isAdministrator(User $user): bool
    {
        return $user->hasPermission('browse_admin');
    }
}
