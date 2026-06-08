<?php

namespace App\Policies;

use App\Models\Settlement;
use App\Models\User;

class SettlementPolicy
{
    public function viewAny(User $user): bool { return $this->allowed($user); }
    public function view(User $user, Settlement $settlement): bool { return $this->allowed($user); }
    public function create(User $user): bool { return $this->allowed($user); }
    public function update(User $user, Settlement $settlement): bool { return $this->allowed($user); }
    public function delete(User $user, Settlement $settlement): bool { return $this->allowed($user); }
    public function deleteAny(User $user): bool { return false; }

    private function allowed(User $user): bool
    {
        return $user->hasPermission('browse_admin');
    }
}
