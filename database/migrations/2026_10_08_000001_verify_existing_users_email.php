<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Кабинет теперь требует подтверждённую почту. Все пользователи, зарегистрированные до введения
 * проверки, уже работают на сайте, поэтому считаем их адреса подтверждёнными, чтобы никто не
 * потерял доступ. Новые регистрации подтверждают почту по ссылке из письма.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Невозможно отличить «подтверждён автоматически» от «подтверждён по ссылке» — откат ничего не делает.
    }
};
