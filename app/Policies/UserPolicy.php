<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function view(User $user, User $target): bool
    {
        return $this->isAdministrator($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdministrator($user);
    }

    public function update(User $user, User $target): bool
    {
        return $this->isAdministrator($user);
    }

    public function delete(User $user, User $target): bool
    {
        return $this->isAdministrator($user) && ! $user->is($target);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function assignRole(User $user, User $target): bool
    {
        return $this->isAdministrator($user);
    }

    public function adjustBalance(User $user, User $target): bool
    {
        return $this->isAdministrator($user);
    }

    public function removeRole(User $user, User $target, Role $role): bool
    {
        if (! $this->isAdministrator($user)) {
            return false;
        }

        if (! $role->permissions()->where('key', 'browse_admin')->exists()) {
            return true;
        }

        $targetHasAnotherAdminRole = $target->rolesAll()
            ->where('id', '!=', $role->id)
            ->contains(fn (Role $targetRole): bool => $targetRole->permissions()
                ->where('key', 'browse_admin')
                ->exists());

        if ($targetHasAnotherAdminRole) {
            return true;
        }

        return User::query()
            ->whereKeyNot($target->getKey())
            ->where(function ($query) {
                $query
                    ->whereHas('role.permissions', fn ($permissionQuery) => $permissionQuery
                        ->where('key', 'browse_admin'))
                    ->orWhereHas('roles.permissions', fn ($permissionQuery) => $permissionQuery
                        ->where('key', 'browse_admin'));
            })
            ->exists();
    }

    private function isAdministrator(User $user): bool
    {
        return $user->hasPermission('browse_admin');
    }
}
