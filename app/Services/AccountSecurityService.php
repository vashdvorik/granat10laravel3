<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AccountSecurityService
{
    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw new RuntimeException('Текущий пароль неверный.');
        }

        $user->password = Hash::make($newPassword);
        $user->save();
    }

    public function deleteShop(User $user, string $password): string
    {
        $seller = $user->seller;

        if (!$seller) {
            throw new RuntimeException('Магазин не найден');
        }

        if (!Hash::check($password, $user->password)) {
            throw new RuntimeException('Неверный пароль');
        }

        $sellerName = $seller->name;
        $seller->delete();

        return $sellerName;
    }
}
