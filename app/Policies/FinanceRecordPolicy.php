<?php

namespace App\Policies;

use App\Models\User;

/**
 * Платежи, журнал баланса и покупки VIP — финансовая отчётность: администратор только смотрит.
 * Менять баланс можно через «Изменить баланс» у пользователя, там остаётся причина и запись в журнале.
 */
class FinanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function view(User $user, mixed $record): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, mixed $record): bool
    {
        return false;
    }

    public function delete(User $user, mixed $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function allowed(User $user): bool
    {
        return $user->hasPermission('browse_admin');
    }
}
