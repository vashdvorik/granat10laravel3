<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserRoleService
{
    public function assignPrimaryRole(User $actor, User $target, Role $role): User
    {
        Gate::forUser($actor)->authorize('assignRole', $target);

        return DB::transaction(function () use ($actor, $target, $role): User {
            $lockedUser = User::query()
                ->with(['role.permissions', 'roles.permissions'])
                ->lockForUpdate()
                ->findOrFail($target->getKey());

            $currentRole = $lockedUser->role;

            if ($currentRole?->isNot($role)) {
                Gate::forUser($actor)->authorize('removeRole', [$lockedUser, $currentRole]);
            }

            $lockedUser->role()->associate($role);
            $lockedUser->save();

            activity('user_roles')
                ->causedBy($actor)
                ->performedOn($lockedUser)
                ->withProperties([
                    'old_role_id' => $currentRole?->getKey(),
                    'new_role_id' => $role->getKey(),
                ])
                ->log('Изменение основной роли пользователя');

            return $lockedUser->refresh();
        });
    }

    public function removeRole(User $actor, User $target, Role $role): User
    {
        Gate::forUser($actor)->authorize('removeRole', [$target, $role]);

        return DB::transaction(function () use ($actor, $target, $role): User {
            $lockedUser = User::query()
                ->with(['role.permissions', 'roles.permissions'])
                ->lockForUpdate()
                ->findOrFail($target->getKey());

            $isPrimaryRole = $lockedUser->role_id === $role->getKey();
            $isAdditionalRole = $lockedUser->roles->contains($role);

            if (! $isPrimaryRole && ! $isAdditionalRole) {
                throw ValidationException::withMessages([
                    'role_id' => 'У пользователя нет выбранной роли.',
                ]);
            }

            Gate::forUser($actor)->authorize('removeRole', [$lockedUser, $role]);

            if ($isPrimaryRole) {
                $lockedUser->role()->dissociate();
                $lockedUser->save();
            }

            if ($isAdditionalRole) {
                $lockedUser->roles()->detach($role);
            }

            activity('user_roles')
                ->causedBy($actor)
                ->performedOn($lockedUser)
                ->withProperties([
                    'removed_role_id' => $role->getKey(),
                    'was_primary' => $isPrimaryRole,
                ])
                ->log('Снятие роли пользователя');

            return $lockedUser->refresh();
        });
    }
}
