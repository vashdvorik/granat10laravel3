<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;

class PackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function view(User $user, Package $package): bool
    {
        return $this->isAdministrator($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function update(User $user, Package $package): bool
    {
        return $this->isAdministrator($user);
    }

    public function delete(User $user, Package $package): bool
    {
        return $this->isAdministrator($user);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function isAdministrator(User $user): bool
    {
        return $user->hasPermission('browse_admin');
    }
}
