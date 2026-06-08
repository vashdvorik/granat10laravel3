<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;

class RegionPolicy
{
    public function viewAny(User $user): bool { return $this->allowed($user); }
    public function view(User $user, Region $region): bool { return $this->allowed($user); }
    public function create(User $user): bool { return $this->allowed($user); }
    public function update(User $user, Region $region): bool { return $this->allowed($user); }
    public function delete(User $user, Region $region): bool { return $this->allowed($user); }
    public function deleteAny(User $user): bool { return false; }

    private function allowed(User $user): bool
    {
        return $user->hasPermission('browse_admin');
    }
}
