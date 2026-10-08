<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AccountSecurityService
{
    /**
     * Меняет пароль. Если пользователь пароль никогда не задавал (вошёл через Google), текущий пароль
     * не требуется: он случайный и ему неизвестен. После этого вход по почте и паролю работает.
     */
    public function updatePassword(User $user, ?string $currentPassword, string $newPassword): void
    {
        if ($user->hasPassword() && ! Hash::check((string) $currentPassword, $user->password)) {
            throw new RuntimeException('Текущий пароль неверный.');
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'password_set_at' => now(),
        ])->save();
    }

    /**
     * Удаляет магазин. Подтверждение: пароль, а если пароля нет (вход через Google) — название магазина.
     */
    public function deleteShop(User $user, ?string $password, ?string $shopNameConfirmation = null): string
    {
        $seller = $user->seller;

        if (! $seller) {
            throw new RuntimeException('Магазин не найден');
        }

        if ($user->hasPassword()) {
            if (! Hash::check((string) $password, $user->password)) {
                throw new RuntimeException('Неверный пароль');
            }
        } elseif (mb_strtolower(trim((string) $shopNameConfirmation)) !== mb_strtolower(trim($seller->name))) {
            throw new RuntimeException('Название магазина введено неверно. Введите его точно так, как оно написано выше.');
        }

        $sellerName = $seller->name;
        $seller->delete();

        return $sellerName;
    }
}
